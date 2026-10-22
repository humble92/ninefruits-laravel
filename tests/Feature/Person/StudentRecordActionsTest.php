<?php

use App\Domain\Person\Actions\AcceptInvitation;
use App\Domain\Person\Actions\AssignAdvisor;
use App\Domain\Person\Actions\CancelStatusChange;
use App\Domain\Person\Actions\DecideStatusChange;
use App\Domain\Person\Actions\InvitePerson;
use App\Domain\Person\Actions\MergePeople;
use App\Domain\Person\Actions\RecordStatusChange;
use App\Domain\Person\Actions\RegisterStudent;
use App\Domain\Person\Actions\RequestStatusChange;
use App\Domain\Person\Enums\StatusChangeStatus;
use App\Domain\Person\Enums\StudentStatus;
use App\Domain\Person\Notifications\AccountInvitationNotification;
use App\Domain\Person\Settings\StudentRecordSettings;
use App\Domain\Security\Enums\Permission;
use App\Domain\Security\Enums\Persona;
use App\Domain\Security\Services\PersonaResolver;
use App\Models\Organization\Program;
use App\Models\Person\Instructor;
use App\Models\Person\Person;
use App\Models\Person\StudentRecord;
use App\Models\Person\StudentStatusChangeType;
use App\Models\Security\Activity;
use App\Models\User;
use App\Support\Targeting\TargetCriteria;
use Carbon\CarbonImmutable;
use Database\Seeders\Essential\StatusChangeTypeSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(StatusChangeTypeSeeder::class);
    $this->program = Program::factory()->create(['effective_start_date' => '2000-01-01']);
    $this->staff = User::factory()->withPermissions([Permission::StudentRecordsManage, Permission::StudentStatusChangesApprove, Permission::PeopleManage, Permission::PeopleMerge])->create();
});

function changeType(string $code): StudentStatusChangeType
{
    return StudentStatusChangeType::query()->where('code', $code)->sole();
}

it('registers students with numbers from the configured pattern', function () {
    $settings = app(StudentRecordSettings::class);
    $settings->student_number_pattern = 'S{YY}{SEQ:4}';
    $settings->save();

    $first = app(RegisterStudent::class)->handle(Person::factory()->create(), $this->program, ['admission_date' => '2026-03-02'], $this->staff);
    $second = app(RegisterStudent::class)->handle(Person::factory()->create(), $this->program, ['admission_date' => '2026-03-02'], $this->staff);

    expect($first->student_number)->toBe('S260001')
        ->and($second->student_number)->toBe('S260002')
        ->and($first->org_unit_id)->toBe($this->program->org_unit_id)
        ->and($first->catalog_year)->toBe(2026)
        ->and(Activity::query()->where('event', 'student_record.registered')->count())->toBe(2);
});

it('skips numbers already taken by imported records', function () {
    StudentRecord::factory()->inProgram($this->program)->create(['student_number' => '202600001']);

    $record = app(RegisterStudent::class)->handle(Person::factory()->create(), $this->program, ['admission_date' => '2026-03-02']);

    expect($record->student_number)->toBe('202600002');
});

it('gives a returning graduate a new record and number, keeping the old one', function () {
    $alumnus = StudentRecord::factory()->inProgram($this->program)->graduated()->create(['admission_date' => '2018-03-01']);

    $new = app(RegisterStudent::class)->handle($alumnus->person, $this->program, ['admission_date' => '2026-03-02']);

    expect($new->person_id)->toBe($alumnus->person_id)
        ->and($new->student_number)->not->toBe($alumnus->student_number)
        ->and($alumnus->fresh()->status)->toBe(StudentStatus::Graduated)
        ->and($alumnus->person->studentRecords()->count())->toBe(2);
});

it('lets a student request a leave that another staff member approves', function () {
    $record = StudentRecord::factory()->inProgram($this->program)->create();
    $student = User::factory()->create(['person_id' => $record->person_id]);

    $change = app(RequestStatusChange::class)->handle($record, changeType('leave_of_absence'), CarbonImmutable::parse('2026-09-01'), 'Military service', $student);

    expect(fn () => app(RequestStatusChange::class)->handle($record, changeType('withdrawal'), CarbonImmutable::parse('2026-09-01'), 'again', $student))
        ->toThrow(ValidationException::class);

    app(DecideStatusChange::class)->approve($change, $this->staff, 'Documents checked');

    expect($record->fresh()->status)->toBe(StudentStatus::OnLeave)
        ->and($change->fresh()->status)->toBe(StatusChangeStatus::Approved)
        ->and($change->fresh()->decided_by_id)->toBe($this->staff->id);
});

it('does not let anyone decide their own request', function () {
    $record = StudentRecord::factory()->inProgram($this->program)->create();
    $student = User::factory()->create(['person_id' => $record->person_id]);
    $student->roleAssignments()->delete();
    $change = app(RequestStatusChange::class)->handle($record, changeType('withdrawal'), CarbonImmutable::today(), 'Moving abroad', $student);

    app(DecideStatusChange::class)->approve($change, $student);
})->throws(AuthorizationException::class);

it('rejects requests that do not fit the current status or are staff-only', function () {
    $record = StudentRecord::factory()->inProgram($this->program)->create();
    $student = User::factory()->create(['person_id' => $record->person_id]);

    expect(fn () => app(RequestStatusChange::class)->handle($record, changeType('return_from_leave'), CarbonImmutable::today(), 'x', $student))
        ->toThrow(ValidationException::class)
        ->and(fn () => app(RequestStatusChange::class)->handle($record, changeType('dismissal'), CarbonImmutable::today(), 'x', $student))
        ->toThrow(ValidationException::class);
});

it('lets the requester cancel and records staff decisions directly', function () {
    $record = StudentRecord::factory()->inProgram($this->program)->create();
    $student = User::factory()->create(['person_id' => $record->person_id]);
    $change = app(RequestStatusChange::class)->handle($record, changeType('leave_of_absence'), CarbonImmutable::today(), 'x', $student);

    app(CancelStatusChange::class)->handle($change, $student);

    app(RecordStatusChange::class)->handle($record, changeType('dismissal'), CarbonImmutable::parse('2026-10-01'), 'Disciplinary committee decision', $this->staff);

    $activity = Activity::query()->where('event', 'student_status_change.recorded')->sole();

    expect($change->fresh()->status)->toBe(StatusChangeStatus::Cancelled)
        ->and($record->fresh()->status)->toBe(StudentStatus::Dismissed)
        ->and($activity->reason)->toBe('Disciplinary committee decision')
        ->and(DB::table('audit.record_changes')->where('table_name', 'student_records')->where('record_id', (string) $record->id)->where('operation', 'UPDATE')->exists())->toBeTrue();
});

it('sets the graduation date when graduating', function () {
    $record = StudentRecord::factory()->inProgram($this->program)->create();

    app(RecordStatusChange::class)->handle($record, changeType('graduation'), CarbonImmutable::parse('2027-02-20'), 'Degree conferred', $this->staff);

    expect($record->fresh()->graduation_date->toDateString())->toBe('2027-02-20');
});

it('ends the current primary advisor when assigning a new one', function () {
    $record = StudentRecord::factory()->inProgram($this->program)->create();
    [$first, $second] = Instructor::factory()->count(2)->create();

    app(AssignAdvisor::class)->handle($record, $first, CarbonImmutable::parse('2026-03-01'));
    app(AssignAdvisor::class)->handle($record, $second, CarbonImmutable::parse('2026-09-01'));

    $history = $record->advisorAssignments()->reorder('start_date')->get();

    expect($history->pluck('instructor_id')->all())->toBe([$first->id, $second->id])
        ->and($history->first()->end_date->toDateString())->toBe('2026-08-31')
        ->and($history->last()->end_date)->toBeNull();
});

it('invites a person and creates a verified account from the link', function () {
    Notification::fake();
    $person = Person::factory()->create();

    app(InvitePerson::class)->handle($person, 'New.Student@Example.test', $this->staff);

    $url = null;
    Notification::assertSentOnDemand(AccountInvitationNotification::class, function ($notification) use (&$url) {
        $url = $notification->url;

        return true;
    });

    $token = basename((string) parse_url($url, PHP_URL_PATH));
    $this->get("/invitations/{$token}")->assertOk()->assertSee('new.student@example.test');
    $this->post("/invitations/{$token}", ['password' => 'Correct-Horse-Battery-9', 'password_confirmation' => 'Correct-Horse-Battery-9'])
        ->assertRedirect(route('portal.dashboard'));

    $user = User::query()->where('email', 'new.student@example.test')->sole();

    expect($user->person_id)->toBe($person->id)
        ->and($user->email_verified_at)->not->toBeNull()
        ->and(AcceptInvitation::find($token))->toBeNull();
});

it('merges a duplicate person into another', function () {
    $target = Person::factory()->create(['phone' => null]);
    $duplicate = Person::factory()->withNationalId('TEST-123')->create(['phone' => '010-1111-2222']);
    $record = StudentRecord::factory()->inProgram($this->program)->create(['person_id' => $duplicate->id]);
    $account = User::factory()->create(['person_id' => $duplicate->id]);

    app(MergePeople::class)->handle($duplicate, $target, 'Registered twice', $this->staff);

    expect($record->fresh()->person_id)->toBe($target->id)
        ->and($account->fresh()->person_id)->toBe($target->id)
        ->and($target->fresh()->phone)->toBe('010-1111-2222')
        ->and($target->fresh()->national_id_encrypted)->toBe('TEST-123')
        ->and(Person::query()->wherePii('national_id', 'TEST-123')->pluck('id')->all())->toContain($target->id)
        ->and($duplicate->fresh()->merged_into_person_id)->toBe($target->id);
});

it('derives personas from records', function () {
    $current = StudentRecord::factory()->inProgram($this->program)->create();
    StudentRecord::factory()->inProgram($this->program)->graduated()->create(['person_id' => $current->person_id]);
    $instructor = Instructor::factory()->create();
    app(AssignAdvisor::class)->handle($current, $instructor, CarbonImmutable::today()->subDay());

    $student = User::factory()->create(['person_id' => $current->person_id]);
    $professor = User::factory()->create(['person_id' => $instructor->person_id]);

    expect(app(PersonaResolver::class)->personasFor($student))->toEqualCanonicalizing([Persona::Student, Persona::Alumni])
        ->and(app(PersonaResolver::class)->personasFor($professor))->toEqualCanonicalizing([Persona::Instructor, Persona::Advisor]);
});

it('provides the student as a target for term window eligibility', function () {
    $record = StudentRecord::factory()->inProgram($this->program)->create(['year_level' => 4, 'admission_date' => '2023-03-01', 'tags' => ['exchange']]);

    expect((new TargetCriteria(yearLevels: [4], admissionYearFrom: 2023, orgUnitIds: [$this->program->org_unit_id], tags: ['exchange']))->matches($record))->toBeTrue()
        ->and((new TargetCriteria(yearLevels: [1]))->matches($record))->toBeFalse();
});
