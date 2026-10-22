<?php

use App\Domain\Admission\Actions\DecideApplication;
use App\Domain\Admission\Actions\MatriculateApplicant;
use App\Domain\Admission\Enums\ApplicationStatus;
use App\Domain\Admission\Notifications\ApplicationDecisionNotification;
use App\Domain\Admission\Notifications\ApplicationSubmittedNotification;
use App\Domain\Security\Enums\MfaEnforcement;
use App\Domain\Security\Enums\Permission;
use App\Domain\Security\Enums\Persona;
use App\Domain\Security\Services\PersonaResolver;
use App\Domain\Term\Enums\TermWindowType;
use App\Domain\Term\Services\TermResolver;
use App\Filament\Admin\Resources\Admissions\Applications\Pages\ViewApplication;
use App\Livewire\Portal\ApplicationDetail;
use App\Livewire\Portal\Applications;
use App\Models\Admission\AdmissionCycle;
use App\Models\Admission\Application;
use App\Models\Institution\LookupValue;
use App\Models\Organization\Program;
use App\Models\Person\StudentRecord;
use App\Models\Security\Activity;
use App\Models\Term\Term;
use App\Models\Term\TermWindow;
use App\Models\User;
use App\Support\Targeting\TargetCriteria;
use Database\Seeders\Essential\EssentialSeeder;
use Database\Seeders\Profiles\KoreanUniversityProfileSeeder;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

beforeEach(function () {
    setSecuritySettings(['mfa_enforcement' => MfaEnforcement::Disabled]);
    $this->seed([EssentialSeeder::class, KoreanUniversityProfileSeeder::class]);
    Storage::fake(config('media-library.disk_name'));
    Notification::fake();

    $this->program = Program::factory()->create(['effective_start_date' => '2000-01-01']);
    $this->diploma = LookupValue::query()->ofType(Application::DOCUMENT_TYPE_LOOKUP)->where('code', 'diploma')->sole();
    $this->cycle = AdmissionCycle::factory()->withPrograms([$this->program])->withDocuments([$this->diploma])
        ->create(['code' => '2027-REG', 'offer_response_days' => 7]);
    $this->term = Term::factory()->published()->between(now()->addMonths(2)->toDateString(), now()->addMonths(6)->toDateString())->create(['code' => '2027-SP']);
    $this->officer = User::factory()->withPermissions([Permission::AdminPanelAccess, Permission::ApplicationsView, Permission::ApplicationsReview, Permission::ApplicationsMatriculate])->create();
});

function fakePdf(string $name): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n1 0 obj << /Type /Catalog >> endobj\ntrailer << /Root 1 0 R >>\n%%EOF\n");
}

function submittedApplication(User $applicant, AdmissionCycle $cycle, Program $program, LookupValue $document): Application
{
    $start = Livewire::actingAs($applicant)->test(Applications::class)
        ->set('cycleId', $cycle->id)
        ->set('programId', $program->id)
        ->call('start');

    $application = Application::query()->where('person_id', $applicant->person_id)->where('admission_cycle_id', $cycle->id)->sole();
    $start->assertRedirect(route('portal.applications.show', $application));

    Livewire::actingAs($applicant)->test(ApplicationDetail::class, ['application' => $application])
        ->set('personal.birth_date', '2007-04-05')
        ->set('form.previous_school_name', 'Hanbit High School')
        ->set('form.personal_statement', 'I want to study here.')
        ->set("uploads.{$document->id}", fakePdf('diploma.pdf'))
        ->call('submit')
        ->assertHasNoErrors();

    return $application->refresh();
}

it('takes one account from registration to a student who can register for courses', function () {
    // Registration and email verification
    $this->post('/register', [
        'family_name' => 'Kim', 'given_name' => 'Minji', 'email' => 'minji@example.test',
        'password' => 'correct-horse-battery', 'password_confirmation' => 'correct-horse-battery',
    ])->assertRedirect();

    $user = User::query()->where('email', 'minji@example.test')->sole();
    Notification::assertSentTo($user, VerifyEmail::class);
    expect($user->person->family_name)->toBe('Kim')
        ->and($user->hasVerifiedEmail())->toBeFalse();

    $this->actingAs($user)->get('/portal/applications')->assertRedirect(route('verification.notice'));
    $this->actingAs($user)->get(URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $user->id, 'hash' => sha1($user->email)]));
    $user->refresh();
    expect($user->hasVerifiedEmail())->toBeTrue();
    $this->actingAs($user)->get('/portal/applications')->assertOk()->assertSee($this->cycle->name);

    // Application
    $application = submittedApplication($user, $this->cycle, $this->program, $this->diploma);
    app(PersonaResolver::class)->flush();

    expect($application->status)->toBe(ApplicationStatus::Submitted)
        ->and($application->application_number)->toBe('2027-REG-00001')
        ->and($application->person->birth_date->toDateString())->toBe('2007-04-05')
        ->and($application->getMedia(Application::DOCUMENTS_COLLECTION))->toHaveCount(1)
        ->and(app(PersonaResolver::class)->personasFor($user))->toContain(Persona::Applicant);
    Notification::assertSentTo($user, ApplicationSubmittedNotification::class);

    // Decision, hidden until released
    app(DecideApplication::class)->admit($application, $this->officer, 'Strong statement');
    expect($application->refresh()->applicantStatus())->toBe(ApplicationStatus::UnderReview);
    Notification::assertNotSentTo($user, ApplicationDecisionNotification::class);

    // Staff use their own browser session.
    $this->flushSession();
    $this->actingAs($this->officer)->get('/admin/admissions/applications/'.$application->id)->assertOk();
    Livewire::actingAs($this->officer)->test(ViewApplication::class, ['record' => $application->id])
        ->callAction('release');

    $application->refresh();
    expect($application->applicantStatus())->toBe(ApplicationStatus::Admitted)
        ->and($application->offer_expires_at)->not->toBeNull();
    Notification::assertSentTo($user, ApplicationDecisionNotification::class);

    // Offer accepted by the applicant, then matriculation
    Livewire::actingAs($user)->test(ApplicationDetail::class, ['application' => $application])->call('respond', true)->assertHasNoErrors();
    expect($application->refresh()->status)->toBe(ApplicationStatus::Confirmed);

    $record = app(MatriculateApplicant::class)->handle($application, $this->term, $this->officer);

    expect($record->person_id)->toBe($user->person_id)
        ->and($record->admission_term_id)->toBe($this->term->id)
        ->and($record->admission_date->toDateString())->toBe($this->term->start_date->toDateString())
        ->and($application->refresh()->student_record_id)->toBe($record->id)
        ->and(Activity::query()->where('event', 'application.matriculated')->where('causer_id', $this->officer->id)->exists())->toBeTrue();

    // Same account, now a student: signs in with the same email and is eligible for the
    // freshman registration window.
    app(PersonaResolver::class)->flush();
    $personas = app(PersonaResolver::class)->personasFor($user->refresh());
    expect($personas)->toContain(Persona::Student)->not->toContain(Persona::Applicant);

    TermWindow::factory()->for($this->term)->ofType(TermWindowType::Registration)
        ->eligibleFor(new TargetCriteria(yearLevels: [1], studentStatuses: ['enrolled']))->create();
    expect(app(TermResolver::class)->termsOpenFor(TermWindowType::Registration, $record)->pluck('id')->all())->toBe([$this->term->id]);

    Auth::logout();
    $this->flushSession();
    $this->post('/login', ['email' => 'minji@example.test', 'password' => 'correct-horse-battery'])->assertRedirect('/portal');
    $this->get('/portal/records')->assertOk()->assertSee($record->student_number);
});

it('lets a graduate apply again with the same account and keeps the earlier record', function () {
    $earlier = StudentRecord::factory()->inProgram($this->program)->graduated()->create(['student_number' => '201900001']);
    $graduate = User::factory()->create(['person_id' => $earlier->person_id]);

    $application = submittedApplication($graduate, $this->cycle, $this->program, $this->diploma);
    expect($application->person->birth_date?->toDateString())->not->toBe('2007-04-05'); // identity is the registrar's once a record exists

    app(DecideApplication::class)->admit($application, $this->officer);
    Application::query()->whereKey($application->id)->update(['decision_released_at' => now()]);
    Livewire::actingAs($graduate)->test(ApplicationDetail::class, ['application' => $application->refresh()])->call('respond', true);
    $record = app(MatriculateApplicant::class)->handle($application->refresh(), $this->term, $this->officer);

    expect(StudentRecord::query()->where('person_id', $graduate->person_id)->pluck('student_number')->sort()->values()->all())
        ->toBe(['201900001', $record->student_number])
        ->and($earlier->fresh()->status->value)->toBe('graduated')
        ->and(User::query()->where('person_id', $graduate->person_id)->count())->toBe(1);
});
