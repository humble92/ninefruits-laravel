<?php

use App\Domain\Admission\Actions\DecideApplication;
use App\Domain\Admission\Actions\MatriculateApplicant;
use App\Domain\Admission\Actions\ReleaseDecisions;
use App\Domain\Admission\Actions\RespondToOffer;
use App\Domain\Admission\Actions\StartApplication;
use App\Domain\Admission\Actions\SubmitApplication;
use App\Domain\Admission\Actions\WithdrawApplication;
use App\Domain\Admission\Enums\ApplicationStatus;
use App\Domain\Admission\Notifications\ApplicationDecisionNotification;
use App\Domain\Admission\Settings\AdmissionSettings;
use App\Domain\Person\Actions\MergePeople;
use App\Domain\Security\Enums\Permission;
use App\Domain\Security\Enums\ScopeType;
use App\Models\Admission\AdmissionCycle;
use App\Models\Admission\Application;
use App\Models\Institution\LookupValue;
use App\Models\Organization\Program;
use App\Models\Person\Person;
use App\Models\Term\Term;
use App\Models\User;
use Database\Seeders\Essential\EssentialSeeder;
use Database\Seeders\Profiles\KoreanUniversityProfileSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed([EssentialSeeder::class, KoreanUniversityProfileSeeder::class]);
    Notification::fake();

    $this->program = Program::factory()->create(['effective_start_date' => '2000-01-01']);
    $this->otherProgram = Program::factory()->create(['effective_start_date' => '2000-01-01']);
    $this->diploma = LookupValue::query()->ofType(Application::DOCUMENT_TYPE_LOOKUP)->where('code', 'diploma')->sole();
    $this->cycle = AdmissionCycle::factory()->withPrograms([$this->program, $this->otherProgram])->withDocuments([$this->diploma])->create();
    $this->applicant = User::factory()->create(['person_id' => Person::factory()->create()->id]);
    $this->reviewer = User::factory()->withPermissions([Permission::ApplicationsView, Permission::ApplicationsReview, Permission::ApplicationsMatriculate])->create();
});

function errorsOf(Closure $callback): array
{
    try {
        $callback();
    } catch (ValidationException $exception) {
        return array_keys($exception->errors());
    }

    return [];
}

it('allows one open application per cycle and only to offered programs in open cycles', function () {
    $start = app(StartApplication::class);
    $notOffered = Program::factory()->create(['effective_start_date' => '2000-01-01']);

    expect(errorsOf(fn () => $start->handle($this->applicant, $this->cycle, $notOffered)))->toBe(['program_id']);

    $first = $start->handle($this->applicant, $this->cycle, $this->program);
    expect(errorsOf(fn () => $start->handle($this->applicant, $this->cycle, $this->otherProgram)))->toBe(['admission_cycle_id']);

    app(WithdrawApplication::class)->handle($first, $this->applicant);
    expect($start->handle($this->applicant, $this->cycle, $this->otherProgram)->status)->toBe(ApplicationStatus::Draft);

    $closed = AdmissionCycle::factory()->closed()->withPrograms([$this->program])->create();
    $unpublished = AdmissionCycle::factory()->withPrograms([$this->program])->create(['is_published' => false]);
    expect(errorsOf(fn () => $start->handle($this->applicant, $closed, $this->program)))->toBe(['admission_cycle_id'])
        ->and(errorsOf(fn () => $start->handle($this->applicant, $unpublished, $this->program)))->toBe(['admission_cycle_id']);
});

it('refuses to submit incomplete applications', function () {
    $application = app(StartApplication::class)->handle($this->applicant, $this->cycle, $this->program);

    expect(errorsOf(fn () => app(SubmitApplication::class)->handle($application, $this->applicant)))
        ->toBe(['previous_school_name', 'personal_statement', 'documents']);
});

it('keeps decisions changeable and hidden until released, then notifies once', function () {
    $application = Application::factory()->forCycle($this->cycle, $this->program)->submitted()->create(['person_id' => $this->applicant->person_id]);
    $decide = app(DecideApplication::class);

    $decide->reject($application, $this->reviewer, 'Capacity');
    $decide->admit($application, $this->reviewer, 'Reconsidered');
    expect($application->applicantStatus())->toBe(ApplicationStatus::UnderReview);

    expect(app(ReleaseDecisions::class)->handle([$application, $application], $this->reviewer))->toBe(1);
    Notification::assertSentToTimes($this->applicant, ApplicationDecisionNotification::class, 1);

    expect(errorsOf(fn () => $decide->reject($application->refresh(), $this->reviewer, 'Too late')))->toBe(['status']);
});

it('releases immediately in cycles that say so', function () {
    $rolling = AdmissionCycle::factory()->rolling()->withPrograms([$this->program])->create();
    $application = Application::factory()->forCycle($rolling, $this->program)->submitted()->create(['person_id' => $this->applicant->person_id]);

    app(DecideApplication::class)->admit($application, $this->reviewer);

    expect($application->refresh()->decision_released_at)->not->toBeNull();
    Notification::assertSentTo($this->applicant, ApplicationDecisionNotification::class);
});

it('limits reviewers to their scope and keeps applicants out of staff decisions', function () {
    $application = Application::factory()->forCycle($this->cycle, $this->program)->submitted()->create(['person_id' => $this->applicant->person_id]);
    $otherUnitReviewer = User::factory()->withPermissions([Permission::ApplicationsReview], ScopeType::OrgUnit, $this->otherProgram->org_unit_id)->create();

    expect(fn () => app(DecideApplication::class)->admit($application, $otherUnitReviewer))->toThrow(AuthorizationException::class)
        ->and(fn () => app(DecideApplication::class)->admit($application, $this->applicant))->toThrow(AuthorizationException::class)
        ->and(fn () => app(MatriculateApplicant::class)->handle($application, Term::factory()->published()->create(), $otherUnitReviewer))->toThrow(AuthorizationException::class);
});

it('accepts offers only before the deadline, and lets staff record a late answer with a reason', function () {
    $application = Application::factory()->forCycle($this->cycle, $this->program)->admitted()->released()->create([
        'person_id' => $this->applicant->person_id,
    ]);
    $application->forceFill(['offer_expires_at' => now()->subMinute()])->save();
    $respond = app(RespondToOffer::class);

    expect(errorsOf(fn () => $respond->handle($application, true, $this->applicant)))->toBe(['status'])
        ->and(errorsOf(fn () => $respond->handle($application, true, $this->reviewer)))->toBe(['reason']);

    $respond->handle($application, true, $this->reviewer, 'Accepted by phone on the deadline day');
    expect($application->refresh()->status)->toBe(ApplicationStatus::Confirmed);
});

it('matriculates only confirmed applications, once', function () {
    $term = Term::factory()->published()->create();
    $admitted = Application::factory()->forCycle($this->cycle, $this->program)->admitted()->released()->create(['person_id' => $this->applicant->person_id]);

    expect(errorsOf(fn () => app(MatriculateApplicant::class)->handle($admitted, $term, $this->reviewer)))->toBe(['status']);

    $admitted->forceFill(['status' => ApplicationStatus::Confirmed])->save();
    app(MatriculateApplicant::class)->handle($admitted, $term, $this->reviewer);

    expect(errorsOf(fn () => app(MatriculateApplicant::class)->handle($admitted->refresh(), $term, $this->reviewer)))->toBe(['status']);
});

it('hides registration when the institution turns it off', function () {
    $settings = app(AdmissionSettings::class);
    $settings->applicant_registration_enabled = false;
    $settings->save();

    $this->get('/register')->assertNotFound();
    $this->post('/register', ['family_name' => 'X', 'email' => 'x@example.test', 'password' => 'correct-horse-battery', 'password_confirmation' => 'correct-horse-battery'])
        ->assertSessionHasErrors('email');
    expect(User::query()->where('email', 'x@example.test')->exists())->toBeFalse();
});

it('expires stale drafts and drafts of closed cycles', function () {
    $closed = AdmissionCycle::factory()->closed()->withPrograms([$this->program])->create();
    $inClosed = Application::factory()->forCycle($closed, $this->program)->create();
    $fresh = Application::factory()->forCycle($this->cycle, $this->program)->create();
    $stale = Application::factory()->forCycle($this->cycle, $this->otherProgram)->create();
    Application::query()->whereKey($stale->id)->update(['updated_at' => now()->subDays(200)]);

    $this->artisan('admission:expire-drafts', ['--dry-run' => true])->expectsOutputToContain('2 draft(s)')->assertSuccessful();
    $this->artisan('admission:expire-drafts')->assertSuccessful();

    expect($inClosed->refresh()->status)->toBe(ApplicationStatus::Expired)
        ->and($stale->refresh()->status)->toBe(ApplicationStatus::Expired)
        ->and($fresh->refresh()->status)->toBe(ApplicationStatus::Draft);
});

it('matriculates a cycle from the command line', function () {
    $term = Term::factory()->published()->create(['code' => '2027-SP']);
    $confirmed = Application::factory()->forCycle($this->cycle, $this->program)->confirmed()->count(2)->create();
    $actor = User::factory()->withPermissions([Permission::ApplicationsMatriculate])->create(['email' => 'admissions@example.test']);

    $this->artisan('admission:matriculate', ['cycle' => $this->cycle->code, '--term' => '2027-SP'])->assertFailed();
    $this->artisan('admission:matriculate', ['cycle' => $this->cycle->code, '--term' => '2027-SP', '--actor' => $actor->email, '--dry-run' => true])
        ->expectsOutputToContain('2 confirmed applicant(s)')->assertSuccessful();
    expect($confirmed->fresh()->pluck('student_record_id')->filter())->toBeEmpty();

    $this->artisan('admission:matriculate', ['cycle' => $this->cycle->code, '--term' => '2027-SP', '--actor' => $actor->email])->assertSuccessful();
    expect($confirmed->fresh()->pluck('student_record_id')->filter())->toHaveCount(2);
});

it('moves applications when merging a duplicate applicant into an earlier person', function () {
    $graduate = Person::factory()->create();
    $duplicate = Person::factory()->create();
    $application = Application::factory()->forCycle($this->cycle, $this->program)->submitted()->create(['person_id' => $duplicate->id]);
    $registrar = User::factory()->withPermissions([Permission::PeopleManage, Permission::PeopleMerge])->create();

    app(MergePeople::class)->handle($duplicate, $graduate, 'Same person: applied with a new account', $registrar);

    expect($application->refresh()->person_id)->toBe($graduate->id);

    $other = Person::factory()->create();
    Application::factory()->forCycle($this->cycle, $this->otherProgram)->submitted()->create(['person_id' => $other->id]);

    expect(errorsOf(fn () => app(MergePeople::class)->handle($other, $graduate, 'Same person', $registrar)))->toBe(['target_id']);
});
