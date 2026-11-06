<?php

use App\Domain\Grading\Enums\GradeStatus;
use App\Domain\Person\Enums\StudentStatus;
use App\Domain\Registration\Data\RegistrationContext;
use App\Domain\Registration\Enums\AttemptAction;
use App\Domain\Registration\Enums\AttemptOutcome;
use App\Domain\Registration\Enums\EnrollmentStatus;
use App\Domain\Registration\Enums\RuleCode;
use App\Domain\Registration\Events\EnrollmentConfirmed;
use App\Domain\Registration\Rules\RegistrationRule;
use App\Domain\Registration\Services\RegistrationRuleEngine;
use App\Domain\Registration\Services\RuleCatalog;
use App\Domain\Term\Enums\TermStatus;
use App\Models\Grading\TermRecord;
use App\Models\Offering\Section;
use App\Models\Person\StudentRecord;
use App\Models\Registration\Enrollment;
use App\Models\Registration\RegistrationAttempt;
use App\Models\Registration\RegistrationHold;
use App\Models\Security\Activity;
use App\Models\Term\Term;
use App\Models\User;
use App\Support\Setup\DemoOptions;
use Database\Seeders\Demo\DemoAccountSeeder;
use Database\Seeders\Demo\DemoCurriculumSeeder;
use Database\Seeders\Demo\DemoGradeHistorySeeder;
use Database\Seeders\Demo\DemoPeopleSeeder;
use Database\Seeders\Demo\DemoReferenceSeeder;
use Database\Seeders\Demo\DemoRegistrationSeeder;
use Database\Seeders\Essential\EssentialSeeder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

it('writes demo registrations and last term grades in bulk with consistent counters, attempts, events and term records', function () {
    Event::fake([EnrollmentConfirmed::class]);
    Storage::fake('local');
    $this->seed(EssentialSeeder::class);
    $this->artisan('setup:demo', ['--force' => true])->assertSuccessful();
    $this->artisan('setup:demo', ['--force' => true, '--student-accounts' => true])->assertSuccessful();

    $current = Term::query()->where('status', TermStatus::Published)->where('start_date', '<=', now()->toDateString())->orderByDesc('start_date')->firstOrFail();
    $past = Term::query()->where('status', TermStatus::GradesClosed)->orderByDesc('start_date')->firstOrFail();
    $sections = Section::query()->where('term_id', $current->id)->withCount(['enrollments as active_count' => fn ($query) => $query->active()])->get();
    $enrollments = Enrollment::query()->where('term_id', $current->id)->active()->get();
    $attempts = RegistrationAttempt::query()->where('term_id', $current->id)->where('action', AttemptAction::Enroll)->get();
    $ruleCodes = array_map(fn (RegistrationRule $rule) => $rule->code()->value, app(RuleCatalog::class)->all());
    $confirmed = Event::dispatched(EnrollmentConfirmed::class)->map(fn (array $arguments) => $arguments[0]->enrollment->id)->all();
    $audited = Activity::query()->where('event', 'enrollment.staff_enrolled')->where('subject_type', (new Enrollment)->getMorphClass())
        ->whereIn('subject_id', $enrollments->modelKeys())->pluck('subject_id')->all();

    expect($enrollments)->not->toBeEmpty()
        ->and($sections->every(fn (Section $section) => $section->enrolled_count === $section->active_count && $section->enrolled_count <= $section->capacity))->toBeTrue()
        ->and($attempts->where('outcome', AttemptOutcome::Succeeded)->count())->toBe($enrollments->count())
        ->and($attempts->every(fn (RegistrationAttempt $attempt) => array_slice(array_column($attempt->results, 'code'), 0, count($ruleCodes)) === $ruleCodes))->toBeTrue()
        ->and($attempts->where('outcome', AttemptOutcome::Refused)->every(fn (RegistrationAttempt $attempt) => collect($attempt->results)
            ->contains(fn (array $result) => ! $result['passed'] && ! $result['overridden'] && $result['severity'] === 'block')))->toBeTrue()
        ->and($enrollments->groupBy('student_record_id')->every(fn ($taken) => $taken->sum(fn (Enrollment $enrollment) => (float) $enrollment->credits) <= 18))->toBeTrue()
        ->and($enrollments->where('is_retake', true)->every(fn (Enrollment $enrollment) => Enrollment::query()->whereKey($enrollment->repeats_enrollment_id)
            ->where('student_record_id', $enrollment->student_record_id)->where('status', EnrollmentStatus::Completed)->exists()))->toBeTrue()
        ->and(array_diff($enrollments->modelKeys(), $confirmed))->toBe([])
        ->and($audited)->toHaveCount($enrollments->count());

    $copied = Section::query()->where('term_id', $past->id)->whereNotNull('copied_from_section_id')
        ->withCount(['enrollments as completed_count' => fn ($query) => $query->where('status', EnrollmentStatus::Completed)])->get();
    $graded = Enrollment::query()->whereIn('section_id', $copied->modelKeys())->where('grade_status', GradeStatus::Finalized)->distinct()->pluck('student_record_id');

    expect($graded)->not->toBeEmpty()
        ->and($copied->every(fn (Section $section) => $section->enrolled_count === $section->completed_count))->toBeTrue()
        ->and(TermRecord::query()->where('term_id', $past->id)->whereIn('student_record_id', $graded)->count())->toBe($graded->count());

    $enrolled = StudentRecord::query()->where('status', StudentStatus::Enrolled)->count();
    $account = User::query()->where('email', 'like', 'student%@'.config('setup.demo.email_domain'))->firstOrFail();

    expect(StudentRecord::query()->where('status', StudentStatus::Enrolled)->whereDoesntHave('person.user')->exists())->toBeFalse()
        ->and(count(explode("\n", trim(Storage::disk('local')->get('load-accounts.csv')))) - 1)->toBe($enrolled)
        ->and(Hash::check((string) config('setup.demo.password'), $account->password))->toBeTrue();
});

it('records the rule results the rule engine gives for each demo registration attempt', function () {
    // Up to the registrations only: later seeders change students (graduation) and their history.
    app()->instance(DemoOptions::class, new DemoOptions);
    $this->seed([EssentialSeeder::class, DemoAccountSeeder::class, DemoReferenceSeeder::class, DemoPeopleSeeder::class, DemoCurriculumSeeder::class,
        DemoGradeHistorySeeder::class, DemoRegistrationSeeder::class]);

    $current = Term::query()->where('status', TermStatus::Published)->where('start_date', '<=', now()->toDateString())->orderByDesc('start_date')->firstOrFail();
    $engine = app(RegistrationRuleEngine::class);
    // The results are stored as jsonb, which keeps its own key order.
    $sorted = function (array $value) use (&$sorted): array {
        if (! array_is_list($value)) {
            ksort($value);
        }

        return array_map(fn ($item) => is_array($item) ? $sorted($item) : $item, $value);
    };
    // Holds placed after the registrations would change the hold rule's answer now.
    $attempts = RegistrationAttempt::query()->where('term_id', $current->id)->where('action', AttemptAction::Enroll)
        ->whereNotIn('student_record_id', RegistrationHold::query()->select('student_record_id'))
        ->orderBy('id')->get()->groupBy('student_record_id');
    $compared = 0;
    $refused = [];

    foreach ($attempts as $recordId => $tries) {
        $record = StudentRecord::query()->findOrFail($recordId);
        $plan = new Collection;

        // Replayed in order: each attempt saw the sections the student had taken before it.
        foreach ($tries as $attempt) {
            $section = Section::query()->findOrFail($attempt->section_id);
            $context = new RegistrationContext($record, $section, AttemptAction::Enroll, $attempt->attempted_at, User::query()->findOrFail($attempt->actor_id),
                true, [RuleCode::Window], $plan->where('section_id', '!=', $section->id)->values());
            $expected = json_decode(json_encode($engine->evaluate($context)->toArray(), JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);

            expect($sorted(array_slice($attempt->results, 0, count($expected))))->toBe($sorted($expected));
            $compared++;
            $refused = [...$refused, ...array_column(array_filter($expected, fn (array $result) => ! $result['passed']), 'code')];

            if ($attempt->outcome === AttemptOutcome::Succeeded) {
                $plan->push(Enrollment::query()->active()->where('student_record_id', $recordId)->where('section_id', $section->id)
                    ->with(['section.meetings', 'section.course'])->sole());
            }
        }
    }

    // The demo has students on academic warning (12 credits) among the registered ones.
    expect($compared)->toBeGreaterThan(100)
        ->and($refused)->toContain(RuleCode::TimeConflict->value, RuleCode::Requisites->value, RuleCode::CreditLimit->value);
});
