<?php

use App\Domain\Grading\Enums\EvaluationMethod;
use App\Domain\Grading\Enums\GradeChangeStatus;
use App\Domain\Grading\Enums\GradeStatus;
use App\Domain\Registration\Actions\ManageHolds;
use App\Domain\Registration\Enums\HoldType;
use App\Domain\Security\Actions\SyncPermissionCatalog;
use App\Domain\Security\Enums\MfaEnforcement;
use App\Domain\Security\Enums\Permission;
use App\Filament\Admin\Pages\ManageGradingSettings;
use App\Filament\Admin\Resources\Grading\GradeChanges\GradeChangeRequestResource;
use App\Filament\Admin\Resources\Grading\GradeChanges\Pages\ListGradeChangeRequests;
use App\Filament\Admin\Resources\Grading\Sections\Pages\ListSectionGrades;
use App\Filament\Admin\Resources\Grading\Sections\SectionGradeResource;
use App\Filament\Admin\Resources\Grading\TermRecords\Pages\ListTermRecords;
use App\Filament\Admin\Resources\Grading\TermRecords\TermRecordResource;
use App\Livewire\Portal\GradeEntry;
use App\Livewire\Portal\MyGrades;
use App\Models\Grading\GradeChangeRequest;
use App\Models\Grading\TermRecord;
use App\Models\Offering\Section;
use App\Models\Person\StudentRecord;
use App\Models\Registration\Enrollment;
use App\Models\Term\Term;
use App\Models\User;
use Database\Seeders\Demo\DemoAccountSeeder;
use Database\Seeders\Demo\DemoCurriculumSeeder;
use Database\Seeders\Demo\DemoGradeHistorySeeder;
use Database\Seeders\Demo\DemoGradingSeeder;
use Database\Seeders\Demo\DemoPeopleSeeder;
use Database\Seeders\Demo\DemoReferenceSeeder;
use Database\Seeders\Demo\DemoRegistrationSeeder;
use Database\Seeders\Essential\EssentialSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    Notification::fake();
    setSecuritySettings(['mfa_enforcement' => MfaEnforcement::Disabled]);
    $this->seed([EssentialSeeder::class, DemoAccountSeeder::class, DemoReferenceSeeder::class, DemoPeopleSeeder::class, DemoCurriculumSeeder::class,
        DemoGradeHistorySeeder::class, DemoRegistrationSeeder::class, DemoGradingSeeder::class]);
    app(SyncPermissionCatalog::class)->handle();
    Filament::setCurrentPanel('admin');
    $this->registrar = User::query()->where('email', 'registrar@demo.test')->sole();
    $this->manager = User::factory()->withPermissions(Permission::cases())->create();
});

it('seeds grades, term records and a change request, and renders the grading pages', function () {
    expect(Enrollment::query()->where('grade_status', GradeStatus::Finalized)->count())->toBeGreaterThan(0)
        ->and(Enrollment::query()->where('grade_status', GradeStatus::Submitted)->count())->toBeGreaterThan(0)
        ->and(TermRecord::query()->whereNotNull('closed_at')->where('academic_standing', 'warning')->count())->toBeGreaterThan(0)
        ->and(TermRecord::query()->where('is_honors', true)->count())->toBeGreaterThan(0)
        ->and(GradeChangeRequest::query()->where('status', GradeChangeStatus::Pending)->count())->toBe(1);

    $section = Section::query()->whereHas('enrollments')->first();
    $record = TermRecord::query()->first()->studentRecord;

    foreach ([SectionGradeResource::getUrl('index'), GradeChangeRequestResource::getUrl('index'), TermRecordResource::getUrl('index'),
        ManageGradingSettings::getUrl(), route('portal.grades.entry', $section), route('transcripts.show', $record)] as $url) {
        $this->actingAs($this->manager)->get($url)->assertOk();
    }

    $student = User::query()->where('email', 'student@demo.test')->sole();
    $this->actingAs($student)->get(route('portal.grades'))->assertOk();
    $this->actingAs($student)->get(route('portal.grades.transcript'))->assertOk();
    $this->actingAs($student)->get(route('transcripts.show', $record))->assertForbidden();
});

it('finalizes a submitted section and approves the pending change request as someone else', function () {
    $section = Section::query()->whereHas('enrollments', fn ($query) => $query->where('grade_status', GradeStatus::Submitted))->first();

    Livewire::actingAs($this->registrar)->test(ListSectionGrades::class)
        ->filterTable('term_id', $section->term_id)
        ->callTableAction('finalize', $section);

    expect($section->enrollments()->where('grade_status', GradeStatus::Finalized)->count())->toBeGreaterThan(0)
        ->and($section->enrollments()->where('grade_status', GradeStatus::Submitted)->count())->toBe(0);

    $request = GradeChangeRequest::query()->pending()->sole();

    Livewire::actingAs($this->registrar)->test(ListGradeChangeRequests::class)
        ->callTableAction('approve', $request, ['note' => 'Checked']);

    expect($request->refresh()->status)->toBe(GradeChangeStatus::Approved)
        ->and($request->enrollment->grade_code)->toBe($request->to_grade_code);
});

it('previews a term close and refuses an unfinished term', function () {
    $current = Term::query()->where('status', 'published')->where('start_date', '<=', now()->toDateString())->orderByDesc('start_date')->first();

    Livewire::actingAs($this->registrar)->test(ListTermRecords::class)
        ->callAction('closeTerm', ['term_id' => $current->id, 'dry_run' => false])
        ->assertNotified();

    expect($current->refresh()->status->value)->toBe('published');
});

it('lets the instructor enter grades in the portal and the student see final ones', function () {
    $instructor = User::query()->where('email', 'instructor@demo.test')->sole();
    $section = Section::query()->taughtBy($instructor->person_id)->where('enrolled_count', '>', 0)
        ->whereHas('term', fn ($query) => $query->where('status', 'published'))->first();
    $enrollment = $section->enrollments()->where('status', 'enrolled')->first();
    $section->forceFill(['evaluation_method' => EvaluationMethod::Absolute])->save();

    // Absolute evaluation: the score shows its grade on the 4.5 scale and on the 4.3 scale.
    Livewire::actingAs($instructor)->test(GradeEntry::class, ['section' => $section])
        ->assertSee(__('grading.evaluation_methods.absolute'))
        ->set("grades.{$enrollment->id}.score", '86')
        ->assertSet("previews.{$enrollment->id}", ['grade' => 'B+', 'comparison' => 'B0'])
        ->call('save')
        ->assertSet('messageTone', 'success');

    expect($enrollment->refresh())->grade_code->toBe('B+')->comparison_grade_code->toBe('B0')->score->toBe('86.00')->grade_status->toBe(GradeStatus::Draft);

    // Drafts stay hidden from the student; final grades show.
    $student = User::query()->whereHas('person.studentRecords', fn ($query) => $query->whereKey($enrollment->student_record_id))->first()
        ?? User::factory()->create(['person_id' => $enrollment->studentRecord->person_id]);
    $final = Enrollment::query()->where('student_record_id', $enrollment->student_record_id)->where('grade_status', GradeStatus::Finalized)->with('course')->first();

    $page = Livewire::actingAs($student)->test(MyGrades::class)->assertDontSee($section->course->code.' ');

    if ($final !== null) {
        $page->assertSee($final->course->code);
    }
});

it('hides grades from a student with a hold that blocks them', function () {
    $record = StudentRecord::query()->whereHas('enrollments', fn ($query) => $query->where('grade_status', GradeStatus::Finalized))->first();
    $student = User::factory()->create(['person_id' => $record->person_id]);
    app(ManageHolds::class)->place($record, HoldType::Academic, 'Integrity investigation', $this->registrar, ['blocks_registration' => false, 'blocks_grades' => true]);

    Livewire::actingAs($student)->test(MyGrades::class)->assertSee(__('grading.grades.blocked', ['type' => HoldType::Academic->label()]));
    $this->actingAs($student)->get(route('portal.grades.transcript'))->assertForbidden();
});
