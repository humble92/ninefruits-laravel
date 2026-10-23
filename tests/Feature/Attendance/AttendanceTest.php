<?php

use App\Domain\Attendance\Actions\GenerateClassMeetings;
use App\Domain\Attendance\Actions\ManageClassMeetings;
use App\Domain\Attendance\Actions\RecordAttendance;
use App\Domain\Attendance\Enums\AttendanceStatus;
use App\Domain\Attendance\Enums\ClassMeetingStatus;
use App\Domain\Attendance\Services\AttendanceSummary;
use App\Domain\Attendance\Services\SectionRoster;
use App\Domain\Security\Actions\SyncPermissionCatalog;
use App\Domain\Security\Enums\MfaEnforcement;
use App\Domain\Security\Enums\Permission;
use App\Filament\Admin\Resources\Offering\Sections\Pages\EditSection;
use App\Filament\Admin\Resources\Offering\Sections\RelationManagers\ClassMeetingsRelationManager;
use App\Livewire\Portal\MyAttendance;
use App\Livewire\Portal\SectionAttendance;
use App\Models\Attendance\AttendanceRecord;
use App\Models\Attendance\ClassMeeting;
use App\Models\Offering\Section;
use App\Models\Offering\SectionInstructor;
use App\Models\Offering\SectionMeeting;
use App\Models\Person\Instructor;
use App\Models\Person\Person;
use App\Models\Person\StudentRecord;
use App\Models\Registration\Enrollment;
use App\Models\Security\Activity;
use App\Models\Term\Term;
use App\Models\User;
use App\Support\Database\DatabaseSessionContext;
use Carbon\CarbonImmutable;
use Database\Seeders\Essential\EssentialSeeder;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    setSecuritySettings(['mfa_enforcement' => MfaEnforcement::Disabled]);
    $this->seed(EssentialSeeder::class);
    app(SyncPermissionCatalog::class)->handle();
    Filament::setCurrentPanel('admin');

    // A term around today; the section meets Mondays and Wednesdays for eight weeks ending last week.
    $this->term = Term::factory()->published()->between(now()->subWeeks(9)->toDateString(), now()->addWeeks(6)->toDateString())->create();
    $this->section = Section::factory()->published()->inTerm($this->term)->create();
    $start = CarbonImmutable::today()->subWeeks(8)->startOfWeek();
    foreach ([1, 3] as $weekday) {
        SectionMeeting::query()->create(['section_id' => $this->section->id, 'weekday' => $weekday, 'start_time' => '09:00', 'end_time' => '10:50', 'start_date' => $start, 'end_date' => $start->addWeeks(8)->subDay()]);
    }

    $instructor = Instructor::factory()->create();
    SectionInstructor::query()->create(['section_id' => $this->section->id, 'instructor_id' => $instructor->id, 'role' => 'primary']);
    $this->teacher = User::factory()->create(['person_id' => $instructor->person_id]);
    $this->staff = User::factory()->withPermissions([Permission::AdminPanelAccess, Permission::SectionsManage, Permission::SectionsView])->create();
    $this->enrollments = Enrollment::factory()->count(3)->inSection($this->section)->create();
    $this->student = User::factory()->create(['person_id' => $this->enrollments[0]->studentRecord->person_id]);
    $this->generate = app(GenerateClassMeetings::class);
    $this->record = app(RecordAttendance::class);
});

it('generates meetings from the weekly meeting times, idempotently', function () {
    expect($this->generate->forSection($this->section, $this->teacher))->toBe(16)
        ->and($this->generate->forSection($this->section, $this->teacher))->toBe(0)
        ->and(ClassMeeting::query()->where('section_id', $this->section->id)->count())->toBe(16)
        ->and(ClassMeeting::query()->first()->meeting_date->isoWeekday())->toBe(1);

    $this->artisan('attendance:generate-sessions', ['term' => 'NOPE'])->assertFailed();
    $this->artisan('attendance:generate-sessions', ['term' => $this->term->code, '--dry-run' => true])->expectsOutputToContain('1 section(s) would be processed.')->assertSuccessful();
    $this->artisan('attendance:generate-sessions', ['term' => $this->term->code])->expectsOutputToContain('1 section(s): 0 meeting(s) created.')->assertSuccessful();

    // Adding and cancelling meetings.
    $manage = app(ManageClassMeetings::class);
    $added = $manage->add($this->section, CarbonImmutable::today()->addDays(2), '14:00', '15:50', $this->teacher);
    expect($added->section_meeting_id)->toBeNull();
    expect(fn () => $manage->add($this->section, CarbonImmutable::today()->addDays(2), '14:00', '15:50', $this->teacher))->toThrow(ValidationException::class, __('attendance.errors.duplicate_meeting'));
    expect(fn () => $manage->add($this->section, CarbonImmutable::today()->addYears(2), '14:00', '15:50', $this->teacher))->toThrow(ValidationException::class);
    expect(fn () => $manage->add($this->section, CarbonImmutable::today(), '15:00', '14:00', $this->teacher))->toThrow(ValidationException::class);
    expect(fn () => $manage->add($this->section, CarbonImmutable::today(), '14:00', '15:00', $this->student))->toThrow(AuthorizationException::class);

    $first = ClassMeeting::query()->where('section_id', $this->section->id)->orderBy('meeting_date')->first();
    $manage->cancel($first, $this->staff, 'Public holiday');
    expect($first->refresh()->status)->toBe(ClassMeetingStatus::Cancelled);
    expect(fn () => $this->record->save($first, [$this->enrollments[0]->id => 'present'], $this->teacher))->toThrow(ValidationException::class, __('attendance.errors.meeting_cancelled'));
    $manage->restore($first, $this->teacher);
    expect($first->refresh()->status)->toBe(ClassMeetingStatus::Scheduled);
});

it('records attendance per meeting and totals it, with the audit trail', function () {
    $this->generate->forSection($this->section);
    [$first, $second] = ClassMeeting::query()->where('section_id', $this->section->id)->orderBy('meeting_date')->limit(2)->get();
    [$a, $b, $c] = $this->enrollments;

    expect($this->record->save($first, [$a->id => 'present', $b->id => 'late', $c->id => 'absent'], $this->teacher, [$b->id => 'Bus']))->toBe(3)
        ->and(AttendanceRecord::query()->where('enrollment_id', $b->id)->sole())->status->toBe(AttendanceStatus::Late)->note->toBe('Bus');
    // Saving the same statuses writes nothing; a change replaces and is audited with the previous value.
    expect($this->record->save($first, [$a->id => 'present', $b->id => 'late', $c->id => 'absent'], $this->teacher, [$b->id => 'Bus']))->toBe(0);
    $this->record->save($first, [$c->id => 'excused'], $this->teacher);
    expect(Activity::query()->where('event', 'attendance.recorded')->latest('id')->first()->properties['changes'][$c->id])->toBe(['absent', 'excused']);
    $this->record->save($second, [$a->id => 'present', $b->id => 'absent', $c->id => null], $this->staff);

    $summary = app(AttendanceSummary::class)->forSection($this->section, app(SectionRoster::class)->enrollments($this->section));
    expect($summary['held'])->toBe(16)
        ->and($summary['students'][$a->id])->toMatchArray(['present' => 2, 'missing' => 14, 'rate' => 12.5])
        ->and($summary['students'][$b->id])->toMatchArray(['late' => 1, 'absent' => 1])
        ->and($summary['students'][$c->id])->toMatchArray(['excused' => 1, 'absent' => 0, 'missing' => 15]);

    $future = app(ManageClassMeetings::class)->add($this->section, CarbonImmutable::today()->addDay(), '09:00', '10:00', $this->teacher);
    expect(fn () => $this->record->save($future, [$a->id => 'present'], $this->teacher))->toThrow(ValidationException::class, __('attendance.errors.meeting_future'));
    $other = Enrollment::factory()->create();
    expect(fn () => $this->record->save($first, [$other->id => 'present'], $this->teacher))->toThrow(ValidationException::class, __('attendance.errors.not_enrolled'));
    expect(fn () => $this->record->save($first, [$a->id => 'present'], $this->student))->toThrow(AuthorizationException::class);
    expect(fn () => $this->record->save($first, [$a->id => 'nope'], $this->teacher))->toThrow(ValidationException::class);
});

it('lets the instructor see the roster and records at the database level, and the student their own', function () {
    $this->generate->forSection($this->section);
    $meeting = ClassMeeting::query()->where('section_id', $this->section->id)->orderBy('meeting_date')->first();
    [$a, $b] = $this->enrollments;
    $this->record->save($meeting, [$a->id => 'present', $b->id => 'absent'], $this->teacher);
    $context = app(DatabaseSessionContext::class);
    $stranger = User::factory()->create(['person_id' => Instructor::factory()->create()->person_id]);

    $context->forUser($this->teacher->id);
    expect(StudentRecord::query()->count())->toBe(3)->and(Person::query()->whereHas('studentRecords')->count())->toBe(3)
        ->and(AttendanceRecord::query()->count())->toBe(2)->and(ClassMeeting::query()->count())->toBe(16);
    expect(AttendanceRecord::query()->where('enrollment_id', $a->id)->update(['status' => 'late']))->toBe(1);
    expect($a->studentRecord->isVisibleTo($this->teacher))->toBeTrue();

    $context->forUser($this->student->id);
    expect(AttendanceRecord::query()->count())->toBe(1)->and(AttendanceRecord::query()->update(['status' => 'present']))->toBe(0)
        ->and(StudentRecord::query()->count())->toBe(1);

    $context->forUser($stranger->id);
    expect(AttendanceRecord::query()->count())->toBe(0)->and(StudentRecord::query()->count())->toBe(0);
    expect($a->studentRecord->isVisibleTo($stranger))->toBeFalse();
});

it('serves the roster CSV and the attendance sheet to the instructor and staff only', function () {
    $this->generate->forSection($this->section);
    $meeting = ClassMeeting::query()->where('section_id', $this->section->id)->orderBy('meeting_date')->first();
    $this->record->save($meeting, [$this->enrollments[0]->id => 'present'], $this->teacher);

    $csv = $this->actingAs($this->teacher)->get(route('sections.roster.csv', $this->section))->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    expect($csv->getContent())->toStartWith("\xEF\xBB\xBF")->toContain($this->enrollments[0]->studentRecord->student_number)
        ->and(Activity::query()->where('event', 'attendance.roster_exported')->exists())->toBeTrue();
    $this->actingAs($this->staff)->get(route('sections.attendance.sheet', $this->section))->assertOk()->assertHeader('Content-Type', 'application/pdf');
    $this->actingAs($this->student)->get(route('sections.roster.csv', $this->section))->assertForbidden();
    $this->actingAs($this->student)->get(route('sections.attendance.sheet', $this->section))->assertForbidden();
});

it('works from the portal pages and the admin relation manager', function () {
    $this->actingAs($this->student)->get(route('portal.attendance.section', $this->section))->assertForbidden();
    $this->actingAs($this->teacher)->get(route('portal.teaching'))->assertOk()->assertSee(__('attendance.portal.open'));

    $page = Livewire::actingAs($this->teacher)->test(SectionAttendance::class, ['section' => $this->section])->assertSee(__('attendance.portal.no_meetings'))
        ->call('generate')->assertSee(__('attendance.portal.generated', ['count' => 16]));
    $meeting = ClassMeeting::query()->where('section_id', $this->section->id)->orderBy('meeting_date')->first();
    $page->call('open', $meeting->id)->assertSee(__('attendance.portal.save'))
        ->call('markAll', 'present')->set("statuses.{$this->enrollments[1]->id}", 'absent')->call('save')->assertSee(__('attendance.portal.saved'));
    expect(AttendanceRecord::query()->where('class_meeting_id', $meeting->id)->count())->toBe(3)
        ->and(AttendanceRecord::query()->where('enrollment_id', $this->enrollments[1]->id)->sole()->status)->toBe(AttendanceStatus::Absent);
    $page->call('cancelMeeting', $meeting->id, 'Snow day')->assertSee(__('attendance.portal.meeting_cancelled'));

    Livewire::actingAs($this->student)->test(MyAttendance::class)->assertSee($this->section->label())->assertSee(__('attendance.statuses.present').' 0');
    Livewire::actingAs($this->student)->test(MyAttendance::class, ['termId' => $this->term->id])->assertOk();

    $this->actingAs($this->staff)->get(route('filament.admin.resources.offering.sections.edit', ['record' => $this->section]))->assertOk();
    Livewire::actingAs($this->staff)->test(ClassMeetingsRelationManager::class, ['ownerRecord' => $this->section, 'pageClass' => EditSection::class])
        ->assertCanSeeTableRecords([$meeting])->callTableAction('restore', $meeting)->assertHasNoTableActionErrors();
    expect($meeting->refresh()->status)->toBe(ClassMeetingStatus::Scheduled);
});
