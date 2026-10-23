<?php

use App\Domain\Assessment\Actions\RecordAssessmentResult;
use App\Domain\Assessment\Actions\RegisterForAssessment;
use App\Domain\Assessment\Enums\AssessmentRegistrationStatus;
use App\Domain\Assessment\Services\AssessmentPassCheck;
use App\Domain\Curriculum\Enums\ProgramRole;
use App\Domain\Curriculum\Enums\RequirementKind;
use App\Domain\Security\Actions\SyncPermissionCatalog;
use App\Domain\Security\Enums\MfaEnforcement;
use App\Domain\Security\Enums\Permission;
use App\Filament\Admin\Resources\Assessment\Results\AssessmentResultResource;
use App\Filament\Admin\Resources\Assessment\Results\Pages\ListAssessmentResults;
use App\Filament\Admin\Resources\Assessment\Sessions\AssessmentSessionResource;
use App\Filament\Admin\Resources\Assessment\Sessions\Pages\EditAssessmentSession;
use App\Filament\Admin\Resources\Assessment\Sessions\RelationManagers\RegistrationsRelationManager;
use App\Filament\Admin\Resources\Assessment\Types\AssessmentTypeResource;
use App\Livewire\Portal\MyAssessments;
use App\Models\Assessment\AssessmentRegistration;
use App\Models\Assessment\AssessmentResult;
use App\Models\Assessment\AssessmentSession;
use App\Models\Assessment\AssessmentType;
use App\Models\Curriculum\CurriculumRequirement;
use App\Models\Curriculum\CurriculumRequirementSet;
use App\Models\Person\StudentRecord;
use App\Models\Security\Activity;
use App\Models\User;
use App\Support\Database\DatabaseSessionContext;
use Carbon\CarbonImmutable;
use Database\Seeders\Essential\EssentialSeeder;
use Database\Seeders\Profiles\KoreanUniversityProfileSeeder;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake(config('media-library.disk_name'));
    setSecuritySettings(['mfa_enforcement' => MfaEnforcement::Disabled]);
    $this->seed([EssentialSeeder::class, KoreanUniversityProfileSeeder::class]);
    app(SyncPermissionCatalog::class)->handle();
    Filament::setCurrentPanel('admin');

    $this->staff = User::factory()->withPermissions([Permission::AdminPanelAccess, Permission::AssessmentsRecord, Permission::AssessmentsManage, Permission::StudentRecordsView])->create();
    $this->record = StudentRecord::factory()->create();
    $this->student = User::factory()->create(['person_id' => $this->record->person_id]);
    $this->english = AssessmentType::query()->where('code', 'ENG_COMP')->with('components')->sole();
    $this->computer = AssessmentType::query()->where('code', 'COMP_EXAM')->sole();
    $this->topik = AssessmentType::query()->where('code', 'TOPIK')->sole();
    $this->session = AssessmentSession::query()->create([
        'assessment_type_id' => $this->english->id, 'held_date' => CarbonImmutable::today()->addWeeks(2), 'capacity' => 2,
        'registration_opens_at' => now()->subDay(), 'registration_closes_at' => now()->addWeek(),
    ]);
    $this->register = app(RegisterForAssessment::class);
    $this->results = app(RecordAssessmentResult::class);
});

function scores(array $values): array
{
    return array_combine(['LISTENING', 'READING', 'WRITING', 'GRAMMAR'], $values);
}

it('lets students register while the window is open and seats remain', function () {
    $registration = $this->register->register($this->session, $this->record, $this->student);
    expect($registration->status)->toBe(AssessmentRegistrationStatus::Registered)->and($registration->registered_by_id)->toBeNull()
        ->and(Activity::query()->where('event', 'assessment.registered')->count())->toBe(1);

    expect(fn () => $this->register->register($this->session, $this->record, $this->student))->toThrow(ValidationException::class, __('assessment.errors.already_registered'));

    $other = StudentRecord::factory()->create();
    $this->register->register($this->session, $other, $this->staff, byStaff: true);
    $third = StudentRecord::factory()->create();
    expect(fn () => $this->register->register($this->session, $third, User::factory()->create(['person_id' => $third->person_id])))->toThrow(ValidationException::class, __('assessment.errors.full'));

    // Cancelling frees the seat; registering again reactivates the row.
    $this->register->cancel($registration, $this->student);
    expect($registration->refresh()->status)->toBe(AssessmentRegistrationStatus::Cancelled)
        ->and($this->register->register($this->session, $third, $this->staff, byStaff: true))->toBeInstanceOf(AssessmentRegistration::class);
    expect(fn () => $this->register->register($this->session, $this->record, $this->student))->toThrow(ValidationException::class, __('assessment.errors.full'));

    $this->session->forceFill(['registration_closes_at' => now()->subMinute(), 'capacity' => null])->save();
    expect(fn () => $this->register->register($this->session, $this->record, $this->student))->toThrow(ValidationException::class, __('assessment.errors.registration_closed'));
    expect($this->register->register($this->session, $this->record, $this->staff, byStaff: true)->status)->toBe(AssessmentRegistrationStatus::Registered);

    expect(fn () => $this->register->register($this->session, $other, $this->student))->toThrow(AuthorizationException::class);
    expect(fn () => $this->register->register($this->session, StudentRecord::factory()->graduated()->create(), $this->staff, byStaff: true))->toThrow(ValidationException::class, __('assessment.errors.record_inactive'));
});

it('decides passes from the scores, the pass score and the area minimums', function () {
    $registration = $this->register->register($this->session, $this->record, $this->staff, byStaff: true);

    $result = $this->results->forRegistration($registration, scores([20, 15, 15, 10]), null, $this->staff);
    expect($result->total_score)->toBe('60.0')->and($result->is_passed)->toBeTrue()->and($result->componentScores()->count())->toBe(4);

    // Entering again replaces the result.
    $again = $this->results->forRegistration($registration, scores([20, 15, 15, 9]), null, $this->staff);
    expect($again->id)->toBe($result->id)->and($again->total_score)->toBe('59.0')->and($again->is_passed)->toBeFalse()
        ->and(AssessmentResult::query()->count())->toBe(1);

    expect(fn () => $this->results->forRegistration($registration, scores([26, 15, 15, 10]), null, $this->staff))->toThrow(ValidationException::class);
    expect(fn () => $this->results->forRegistration($registration, scores([20, 15, 15, null]), null, $this->staff))->toThrow(ValidationException::class);

    // An area minimum fails the student whatever the total; the session can raise the pass score.
    $this->english->components->firstWhere('code', 'WRITING')->forceFill(['min_score' => 10])->save();
    $registration->session->type->load('components');
    expect($this->results->forRegistration($registration, scores([25, 25, 9, 25]), null, $this->staff)->is_passed)->toBeFalse();
    $this->session->forceFill(['pass_score' => 90])->save();
    $registration->load('session.type.components');
    expect($this->results->forRegistration($registration, scores([25, 20, 20, 20]), null, $this->staff)->is_passed)->toBeFalse()
        ->and($this->results->forRegistration($registration, scores([25, 25, 20, 20]), null, $this->staff)->is_passed)->toBeTrue();

    // Pass/fail kinds need a decision; a score kind without pass score too.
    $computerSession = AssessmentSession::query()->create(['assessment_type_id' => $this->computer->id, 'held_date' => CarbonImmutable::today()]);
    $computerRegistration = $this->register->register($computerSession, $this->record, $this->staff, byStaff: true);
    expect(fn () => $this->results->forRegistration($computerRegistration, [], null, $this->staff))->toThrow(ValidationException::class, __('assessment.errors.decision_required'));
    expect($this->results->forRegistration($computerRegistration, [], true, $this->staff)->is_passed)->toBeTrue();
    expect(fn () => $this->register->cancel($computerRegistration, $this->staff))->toThrow(ValidationException::class, __('assessment.errors.has_result'));

    $clerk = User::factory()->withPermissions([Permission::StudentRecordsView])->create();
    expect(fn () => $this->results->forRegistration($registration, scores([25, 25, 20, 20]), null, $clerk))->toThrow(AuthorizationException::class);
});

it('shows results to the student only once published, and external results at once', function () {
    $registration = $this->register->register($this->session, $this->record, $this->staff, byStaff: true);
    $this->results->forRegistration($registration, scores([20, 20, 20, 20]), null, $this->staff);
    $context = app(DatabaseSessionContext::class);

    $stranger = User::factory()->create(['person_id' => StudentRecord::factory()->create()->person_id]);

    $context->forUser($this->student->id);
    expect(AssessmentResult::query()->count())->toBe(0)->and(AssessmentRegistration::query()->count())->toBe(1);
    $context->forUser($stranger->id);
    expect(AssessmentRegistration::query()->count())->toBe(0);

    $context->forUser($this->staff->id);
    $this->results->publish($this->session, $this->staff);
    expect(fn () => $this->results->publish($this->session, $this->staff))->toThrow(ValidationException::class);

    $context->forUser($this->student->id);
    expect(AssessmentResult::query()->count())->toBe(1);
    expect(AssessmentResult::query()->update(['is_passed' => false]))->toBe(0);

    $context->forUser($this->staff->id);
    $external = $this->results->external($this->record, $this->topik, CarbonImmutable::today()->subMonth(), true, $this->staff, 230, 'TOPIK 5', 'T-1', null, UploadedFile::fake()->createWithContent('topik.pdf', "%PDF-1.4\n1 0 obj << /Type /Catalog >> endobj\ntrailer << /Root 1 0 R >>\n%%EOF\n"));
    expect($external->getMedia(AssessmentResult::EVIDENCE_COLLECTION))->toHaveCount(1);
    expect(fn () => $this->results->external($this->record, $this->english, CarbonImmutable::today(), true, $this->staff))->toThrow(ValidationException::class, __('assessment.errors.no_external'));
    expect(fn () => $this->results->external($this->record, $this->topik, CarbonImmutable::tomorrow(), true, $this->staff))->toThrow(ValidationException::class);

    $context->forUser($this->student->id);
    expect(AssessmentResult::query()->count())->toBe(2);

    $this->actingAs($this->student)->get(route('assessments.evidence', $external))->assertOk();
    $this->actingAs($stranger)->get(route('assessments.evidence', $external))->assertForbidden();
    Livewire::actingAs($this->student)->test(MyAssessments::class)->assertSee('TOPIK 5')->assertSee('80.0');

    $context->forUser($this->staff->id);
    $this->results->remove($external, $this->staff, 'Wrong student');
    expect(AssessmentResult::query()->count())->toBe(1)->and(Activity::query()->where('event', 'assessment.result_removed')->exists())->toBeTrue();
});

it('imports results from a CSV and reports problems', function () {
    $other = StudentRecord::factory()->create();
    $this->register->register($this->session, $this->record, $this->staff, byStaff: true);
    $path = tempnam(sys_get_temp_dir(), 'csv');
    file_put_contents($path, implode("\n", [
        'student_number,listening,reading,writing,grammar,absent,note',
        "{$this->record->student_number},20,20,20,20,,Good",
        "{$other->student_number},,,,,Y,",
        '00000000,1,1,1,1,,',
    ]));

    $args = ['type' => 'ENG_COMP', 'held_date' => $this->session->held_date->toDateString(), 'file' => $path, '--actor' => $this->staff->email];
    $this->artisan('assessment:import-results', ['type' => 'NOPE', 'held_date' => '2020-01-01', 'file' => $path, '--actor' => $this->staff->email])->assertFailed();
    $this->artisan('assessment:import-results', [...$args, '--dry-run' => true])->expectsOutputToContain('Dry run: 3 row(s): 1 recorded, 1 absent, 1 error(s).')->assertExitCode(2);
    expect(AssessmentResult::query()->count())->toBe(0);

    $this->artisan('assessment:import-results', $args)->expectsOutputToContain('3 row(s): 1 recorded, 1 absent, 1 error(s).')->assertExitCode(2);
    expect(AssessmentResult::query()->sole())->total_score->toBe('80.0')->is_passed->toBeTrue()->note->toBe('Good')
        ->and(AssessmentRegistration::query()->where('student_record_id', $other->id)->sole()->status)->toBe(AssessmentRegistrationStatus::Absent);

    $this->artisan('assessment:publish-results', ['type' => 'ENG_COMP', 'held_date' => $this->session->held_date->toDateString(), '--actor' => $this->staff->email])
        ->expectsOutputToContain('Published 1 result(s)')->assertSuccessful();
    expect($this->session->refresh()->results_published_at)->not->toBeNull();
    unlink($path);
});

it('counts a passed kind towards the curriculum requirement', function () {
    $set = CurriculumRequirementSet::query()->create([
        'program_id' => $this->record->program_id, 'program_role' => ProgramRole::Primary, 'catalog_year_from' => $this->record->catalog_year - 1,
        'name' => ['en' => 'Requirements', 'ko' => '요건', 'zh_CN' => '要求'],
    ]);
    CurriculumRequirement::query()->create(['curriculum_requirement_set_id' => $set->id, 'kind' => RequirementKind::AssessmentPass, 'assessment_type_id' => $this->english->id]);
    expect(databaseError(fn () => CurriculumRequirement::query()->create(['curriculum_requirement_set_id' => $set->id, 'kind' => RequirementKind::AssessmentPass])))->toContain('curriculum_requirements_kind_check');

    $check = app(AssessmentPassCheck::class);
    expect($check->required($this->record)->sole()['passed'])->toBeFalse()->and($check->hasPassed($this->record, $this->english))->toBeFalse();

    $registration = $this->register->register($this->session, $this->record, $this->staff, byStaff: true);
    $this->results->forRegistration($registration, scores([20, 20, 20, 20]), null, $this->staff);
    expect($check->required($this->record)->sole()['passed'])->toBeTrue();

    Livewire::actingAs($this->student)->test(MyAssessments::class)->assertSee(__('assessment.portal.required'))->assertSee($this->english->name);
});

it('registers, enters and cancels from the admin screens, and registers from the portal', function () {
    foreach ([AssessmentTypeResource::getUrl('index'), AssessmentTypeResource::getUrl('edit', ['record' => $this->english]), AssessmentSessionResource::getUrl('index'),
        AssessmentSessionResource::getUrl('create'), AssessmentSessionResource::getUrl('edit', ['record' => $this->session]), AssessmentResultResource::getUrl('index')] as $url) {
        $this->actingAs($this->staff)->get($url)->assertOk();
    }

    $manager = Livewire::actingAs($this->staff)->test(RegistrationsRelationManager::class, ['ownerRecord' => $this->session, 'pageClass' => EditAssessmentSession::class]);
    $manager->callTableAction('register', data: ['student_number' => $this->record->student_number])->assertHasNoTableActionErrors();
    $registration = AssessmentRegistration::query()->sole();
    $manager->callTableAction('result', $registration, ['scores' => scores([25, 25, 25, 25]), 'passed' => null, 'note' => null])->assertHasNoTableActionErrors();
    expect($registration->result()->sole()->is_passed)->toBeTrue();
    $manager->assertTableActionHidden('cancel', $registration);

    Livewire::actingAs($this->staff)->test(ListAssessmentResults::class)
        ->callTableAction('external', data: ['student_number' => $this->record->student_number, 'assessment_type_id' => $this->topik->id, 'taken_date' => today()->toDateString(), 'passed' => '1', 'level' => 'TOPIK 6'])
        ->assertHasNoTableActionErrors();
    expect(AssessmentResult::query()->where('level', 'TOPIK 6')->exists())->toBeTrue();

    $other = StudentRecord::factory()->create();
    Livewire::actingAs(User::factory()->create(['person_id' => $other->person_id]))->test(MyAssessments::class)
        ->assertSee(__('assessment.portal.register'))->call('register', $this->session->id)->assertSee(__('assessment.portal.registered'))
        ->call('register', $this->session->id)->assertSee(__('assessment.errors.already_registered'));
    expect(AssessmentRegistration::query()->where('student_record_id', $other->id)->exists())->toBeTrue();
});
