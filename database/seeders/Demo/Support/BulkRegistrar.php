<?php

namespace Database\Seeders\Demo\Support;

use App\Domain\Curriculum\Enums\RequisiteKind;
use App\Domain\Offering\Enums\SectionStatus;
use App\Domain\Registration\Data\RegistrationContext;
use App\Domain\Registration\Data\RuleCheck;
use App\Domain\Registration\Data\RuleEvaluation;
use App\Domain\Registration\Data\RuleResult;
use App\Domain\Registration\Enums\AttemptAction;
use App\Domain\Registration\Enums\AttemptOutcome;
use App\Domain\Registration\Enums\EnrollmentSource;
use App\Domain\Registration\Enums\EnrollmentStatus;
use App\Domain\Registration\Enums\RuleCode;
use App\Domain\Registration\Enums\RuleSeverity;
use App\Domain\Registration\Enums\WaitlistStatus;
use App\Domain\Registration\Events\EnrollmentConfirmed;
use App\Domain\Registration\Rules\RegistrationRule;
use App\Domain\Registration\Rules\TimeConflictRule;
use App\Domain\Registration\Services\CourseHistory;
use App\Domain\Registration\Services\RegistrationRuleEngine;
use App\Domain\Security\Services\AuditLogger;
use App\Domain\Term\Enums\TermWindowType;
use App\Domain\Term\Services\TermResolver;
use App\Models\Curriculum\CourseRequisite;
use App\Models\Offering\Section;
use App\Models\Offering\SectionSeatGroup;
use App\Models\Person\StudentRecord;
use App\Models\Registration\CartItem;
use App\Models\Registration\CreditLimitPolicy;
use App\Models\Registration\Enrollment;
use App\Models\Registration\RegistrationAttempt;
use App\Models\Registration\RegistrationHold;
use App\Models\Registration\RegistrationOverride;
use App\Models\Registration\WaitlistEntry;
use App\Models\Term\Term;
use App\Models\Term\TermWindow;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Context;

/**
 * Staff registrations for many students at once, as EnrollStudent would make them with the
 * window waived: each student tries up to ten sections that admit them and keeps up to five.
 * The rules are answered from data loaded once (the term's rule set still applies through
 * the engine) and seats are taken on in-memory counters; enrollments, one attempt row per
 * try with every rule result, and the seat counters are then written in bulk. Each new
 * enrollment then gets what EnrollStudent gives it: the section leaves the student's cart, a
 * waiting entry for it is marked promoted, the confirmation event (LMS sync) and the staff
 * registration activity record.
 */
final class BulkRegistrar
{
    private const int TRIED = 10;

    private const int PER_STUDENT = 5;

    private const int CHUNK = 1000;

    /** @var array<int, list<int>> Accepted course ids by course id */
    private array $accepted = [];

    public function __construct(
        private readonly RegistrationRuleEngine $engine,
        private readonly CourseHistory $history,
        private readonly TermResolver $terms,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  Collection<int, StudentRecord>  $students
     */
    public function register(Term $term, Collection $students, User $registrar, string $reason): void
    {
        $at = CarbonImmutable::now();
        $sections = Section::query()->where('term_id', $term->id)->where('status', SectionStatus::Published)
            ->with(['course', 'version.requisites.requiredCourse', 'meetings', 'seatGroups'])->get();

        if ($sections->isEmpty() || $students->isEmpty()) {
            return;
        }

        $subjects = StudentSubject::forRecords($students);
        $ids = $students->modelKeys();
        $holds = RegistrationHold::query()->whereIn('student_record_id', $ids)->where('blocks_registration', true)->inForce($at)->get()->keyBy('student_record_id');
        $overrides = RegistrationOverride::query()->whereIn('student_record_id', $ids)->where('term_id', $term->id)->inForce($at)->get()->groupBy('student_record_id');
        $policies = CreditLimitPolicy::query()->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('term_type_id')->orWhere('term_type_id', $term->term_type_id))
            ->orderByDesc('priority')->orderBy('id')->get();
        $registrationWindows = $this->terms->openWindows(TermWindowType::Registration, $at)->where('term_id', $term->id);
        $addDropWindows = $this->terms->openWindows(TermWindowType::AddDrop, $at);
        $waiting = WaitlistEntry::query()->whereIn('section_id', $sections->modelKeys())->where('status', WaitlistStatus::Waiting)
            ->toBase()->selectRaw('section_id, count(*) AS waiting')->groupBy('section_id')->pluck('waiting', 'section_id');
        $active = Enrollment::query()->active()->whereIn('student_record_id', $ids)->where('term_id', $term->id)
            ->with(['section.meetings', 'section.course'])->get();
        $existing = $active->groupBy('student_record_id');
        [$passed, $completed] = $this->history($students, $term);
        $request = Context::get('request_id');
        $enrollments = [];
        $attempts = [];
        /** @var array<string, list<string>> $warnings Warnings of each new enrollment by student record and section */
        $warnings = [];

        // Section::admits() with each section's criteria read once (cast attributes are slow in a hot loop).
        $criteria = $sections->map(fn (Section $section) => [$section, $section->eligibility, $section->exclusion->isEveryone() ? null : $section->exclusion]);
        $windowFailed = __('registration.rules.window.failed.'.AttemptAction::Enroll->value);

        foreach ($students as $record) {
            $subject = $subjects[$record->id];
            $policy = $policies->first(fn (CreditLimitPolicy $policy) => $policy->eligibility->matches($subject));
            $studentOverrides = $overrides->get($record->id, new Collection);
            $registrationOpen = $registrationWindows->contains(fn (TermWindow $window) => $window->eligibility->matches($subject));
            $addDropOpen = $addDropWindows->filter(fn (TermWindow $window) => $window->eligibility->matches($subject))->pluck('term_session_id')->all();
            /** @var list<Enrollment> $taken Active enrollments in the term, earlier ones included */
            $taken = $existing->get($record->id, new Collection)->values()->all();
            $added = 0;
            $candidates = $criteria->filter(fn (array $section) => $section[1]->matches($subject) && ($section[2] === null || ! $section[2]->matches($subject)))
                ->map(fn (array $section) => $section[0])->shuffle()->take(self::TRIED);

            foreach ($candidates as $section) {
                // The rules see the other sections, like RegistrationContext::termEnrollments().
                $plan = array_values(array_filter($taken, fn (Enrollment $enrollment) => $enrollment->section_id !== $section->id));
                $context = new RegistrationContext($record, $section, AttemptAction::Enroll, $at, $registrar, true, [RuleCode::Window], new Collection($plan));
                $windowOpen = $registrationOpen || in_array($section->term_session_id, $addDropOpen, true);
                $evaluation = $this->engine->evaluateWith(
                    $context,
                    $studentOverrides->filter(fn (RegistrationOverride $override) => $override->covers($section)),
                    fn (RegistrationRule $rule, array $parameters) => match ($rule->code()) {
                        RuleCode::Window => $windowOpen ? RuleCheck::pass() : RuleCheck::fail($windowFailed),
                        RuleCode::Hold => $this->hold($holds->get($record->id)),
                        // The candidates are the sections that admit the student.
                        RuleCode::Eligibility => RuleCheck::pass(),
                        RuleCode::TimeConflict => $this->timeConflict($section, $plan, $parameters),
                        RuleCode::CreditLimit => $this->creditLimit($section, $plan, $policy),
                        RuleCode::Requisites => $this->requisites($section, $plan, $passed[$record->person_id] ?? [], $at),
                        RuleCode::RepeatCourse => $this->repeatCourse($section, $completed[$record->id] ?? [], $parameters, $at),
                        // Section open, student status, same course: no queries
                        default => $rule->check($context, $parameters),
                    },
                );
                $seat = $evaluation->isBlocked() ? null : $this->findSeat($section, $subject, $at);

                if (! $evaluation->isBlocked() && $seat === null) {
                    $message = $section->waitlist_capacity > (int) ($waiting[$section->id] ?? 0) ? __('registration.capacity.full_waitlist_open') : __('registration.capacity.full');
                    $evaluation = new RuleEvaluation([...$evaluation->results, new RuleResult(RuleCode::Capacity, false, RuleSeverity::Block, false, $message)]);
                } elseif ($seat !== null && count($plan) < count($taken)) {
                    // Already in this section: EnrollStudent refuses it on the unique index.
                    $seat = null;
                    $evaluation = new RuleEvaluation([...$evaluation->results, new RuleResult(RuleCode::DuplicateCourse, false, RuleSeverity::Block, false, __('registration.rules.duplicate_course.failed', ['section' => $section->label()]))]);
                }

                $attempts[] = [
                    'student_record_id' => $record->id,
                    'term_id' => $term->id,
                    'section_id' => $section->id,
                    'action' => AttemptAction::Enroll->value,
                    'outcome' => ($seat === null ? AttemptOutcome::Refused : AttemptOutcome::Succeeded)->value,
                    'results' => json_encode($evaluation->toArray(), JSON_THROW_ON_ERROR),
                    'actor_id' => $registrar->id,
                    'request_id' => $request,
                    'attempted_at' => $at,
                ];

                if ($seat === null) {
                    continue;
                }

                $this->takeSeat($section, $seat);
                $retakeOf = $evaluation->detail('repeats_enrollment_id');
                $enrollment = new Enrollment([
                    'student_record_id' => $record->id,
                    'section_id' => $section->id,
                    'term_id' => $term->id,
                    'course_id' => $section->course_id,
                    'course_version_id' => $section->course_version_id,
                    'credits' => $section->version->credits,
                    'course_category_id' => $section->version->course_category_id,
                    'counts_toward_credit_limit' => $section->version->counts_toward_credit_limit,
                    'seat_group_id' => $seat instanceof SectionSeatGroup ? $seat->id : null,
                    'is_retake' => $retakeOf !== null,
                    'repeats_enrollment_id' => $retakeOf,
                    'source' => EnrollmentSource::Staff,
                    'enrolled_at' => $at,
                    'changed_by_id' => $registrar->id,
                ]);
                $enrollment->setRelation('section', $section);
                $taken[] = $enrollment;
                $enrollments[] = [...$enrollment->getAttributes(), 'created_at' => $at, 'updated_at' => $at];
                $warnings[$record->id.'-'.$section->id] = $evaluation->warnings();

                if (++$added >= self::PER_STUDENT) {
                    break;
                }
            }
        }

        foreach (array_chunk($enrollments, self::CHUNK) as $chunk) {
            Enrollment::query()->insert($chunk);
        }

        foreach (array_chunk($attempts, self::CHUNK) as $chunk) {
            RegistrationAttempt::query()->insert($chunk);
        }

        // Counters only (no updated_at), like SeatAllocator::take.
        foreach ($sections as $section) {
            if ($section->isDirty('enrolled_count')) {
                Section::query()->whereKey($section->id)->toBase()->update(['enrolled_count' => $section->enrolled_count]);
            }

            foreach ($section->seatGroups as $group) {
                if ($group->isDirty('enrolled_count')) {
                    SectionSeatGroup::query()->whereKey($group->id)->toBase()->update(['enrolled_count' => $group->enrolled_count]);
                }
            }
        }

        $created = Enrollment::query()->active()->where('term_id', $term->id)->whereIn('student_record_id', $ids)
            ->whereNotIn('id', $active->modelKeys())->orderBy('id')->get();
        $this->settleCartAndWaitlist($created, $at);

        foreach ($created as $enrollment) {
            EnrollmentConfirmed::dispatch($enrollment);
            $this->audit->record('enrollment.staff_enrolled', $enrollment, $reason, [
                'waived' => [RuleCode::Window->value],
                'warnings' => $warnings[$enrollment->student_record_id.'-'.$enrollment->section_id] ?? [],
            ], $registrar);
        }
    }

    /**
     * EnrollStudent after taking a seat: the section leaves the student's cart, and a waiting
     * entry of the student for it becomes promoted with the new enrollment.
     *
     * @param  Collection<int, Enrollment>  $created
     */
    private function settleCartAndWaitlist(Collection $created, CarbonImmutable $at): void
    {
        if ($created->isEmpty()) {
            return;
        }

        $byPair = $created->keyBy(fn (Enrollment $enrollment) => $enrollment->student_record_id.'-'.$enrollment->section_id);
        $records = $created->pluck('student_record_id')->unique()->values()->all();
        $sections = $created->pluck('section_id')->unique()->values()->all();
        $inPair = fn (CartItem|WaitlistEntry $row) => $byPair->has($row->student_record_id.'-'.$row->section_id);

        $cart = CartItem::query()->whereIn('student_record_id', $records)->whereIn('section_id', $sections)->get()->filter($inPair);

        foreach (array_chunk($cart->modelKeys(), self::CHUNK) as $chunk) {
            CartItem::query()->whereKey($chunk)->delete();
        }

        $waiting = WaitlistEntry::query()->whereIn('student_record_id', $records)->whereIn('section_id', $sections)
            ->where('status', WaitlistStatus::Waiting)->get()->filter($inPair);

        foreach ($waiting as $entry) {
            WaitlistEntry::query()->whereKey($entry->id)->update([
                'status' => WaitlistStatus::Promoted,
                'resolved_at' => $at,
                'enrollment_id' => $byPair->get($entry->student_record_id.'-'.$entry->section_id)?->id,
            ]);
        }
    }

    /**
     * SeatAllocator::find on the loaded counters.
     */
    private function findSeat(Section $section, StudentSubject $subject, CarbonImmutable $at): SectionSeatGroup|string|null
    {
        $reserved = $section->seatGroups->filter(fn (SectionSeatGroup $group) => $group->isReservedAt($at));
        $group = $reserved->first(fn (SectionSeatGroup $group) => $group->enrolled_count < $group->capacity && $group->eligibility->matches($subject));

        if ($group !== null) {
            return $group;
        }

        $unusedReserved = $reserved->sum(fn (SectionSeatGroup $group) => max(0, $group->capacity - $group->enrolled_count));

        return $section->capacity - $section->enrolled_count - $unusedReserved > 0 ? 'general' : null;
    }

    private function takeSeat(Section $section, SectionSeatGroup|string $seat): void
    {
        $section->enrolled_count++;

        if ($seat instanceof SectionSeatGroup) {
            $seat->enrolled_count++;
        }
    }

    private function hold(?RegistrationHold $hold): RuleCheck
    {
        return $hold === null
            ? RuleCheck::pass()
            : RuleCheck::fail(__('registration.rules.hold.failed', ['type' => $hold->hold_type->label()]), ['hold_id' => $hold->id]);
    }

    /**
     * @param  list<Enrollment>  $plan
     * @param  array<string, mixed>  $parameters
     */
    private function timeConflict(Section $section, array $plan, array $parameters): RuleCheck
    {
        $allowed = max(0, (int) ($parameters['allowed_overlap_minutes'] ?? 0));
        $meetings = $section->meetings->where('is_active', true);

        foreach ($plan as $enrollment) {
            foreach ($enrollment->section->meetings as $other) {
                foreach ($meetings as $meeting) {
                    if (TimeConflictRule::overlapMinutes($meeting, $other) > $allowed) {
                        return RuleCheck::fail(__('registration.rules.time_conflict.failed', ['section' => $enrollment->section->label()]), ['conflicts_with' => $enrollment->section_id]);
                    }
                }
            }
        }

        return RuleCheck::pass();
    }

    /**
     * @param  list<Enrollment>  $plan
     */
    private function creditLimit(Section $section, array $plan, ?CreditLimitPolicy $policy): RuleCheck
    {
        if ($policy === null || ! $section->version->counts_toward_credit_limit) {
            return RuleCheck::pass();
        }

        $current = array_sum(array_map(fn (Enrollment $enrollment) => $enrollment->counts_toward_credit_limit ? (float) $enrollment->credits : 0.0, $plan));
        $total = $current + (float) $section->version->credits;
        $limit = (float) $policy->max_credits;
        $details = ['credits' => $total, 'max_credits' => $limit, 'policy_id' => $policy->id];

        return $total <= $limit
            ? RuleCheck::pass(null, $details)
            : RuleCheck::fail(__('registration.rules.credit_limit.failed', ['total' => $total, 'limit' => $limit]), $details);
    }

    /**
     * @param  list<Enrollment>  $plan
     * @param  array<int, list<string|null>>  $passed  Passed grades of the person by course id
     */
    private function requisites(Section $section, array $plan, array $passed, CarbonImmutable $at): RuleCheck
    {
        $thisTerm = array_map(fn (Enrollment $enrollment) => $enrollment->course_id, $plan);
        $missing = [];

        foreach ($section->version->requisites->groupBy(fn (CourseRequisite $requisite) => $requisite->kind->value.'-'.$requisite->group_number) as $group) {
            $met = $group->contains(function (CourseRequisite $requisite) use ($passed, $thisTerm, $at) {
                $accepted = $this->acceptedCourseIds($requisite->required_course_id, $at);
                $concurrent = $requisite->kind === RequisiteKind::Corequisite || $requisite->allows_concurrent;

                foreach ($accepted as $courseId) {
                    foreach ($passed[$courseId] ?? [] as $grade) {
                        if ($requisite->minimum_grade === null || $this->history->meetsMinimum($grade, $requisite->minimum_grade)) {
                            return true;
                        }
                    }
                }

                return $concurrent && array_intersect($accepted, $thisTerm) !== [];
            });

            if (! $met) {
                $missing[] = $group->map(fn (CourseRequisite $requisite) => $requisite->requiredCourse->code)->implode(' / ');
            }
        }

        return $missing === []
            ? RuleCheck::pass()
            : RuleCheck::fail(__('registration.rules.requisites.failed', ['courses' => implode(', ', $missing)]), ['missing' => $missing]);
    }

    /**
     * @param  array<int, list<Enrollment>>  $completed  Completed enrollments of the record in other terms by course id, newest first
     * @param  array<string, mixed>  $parameters
     */
    private function repeatCourse(Section $section, array $completed, array $parameters, CarbonImmutable $at): RuleCheck
    {
        if ($section->version->is_repeatable) {
            return RuleCheck::pass();
        }

        $earlier = collect($this->acceptedCourseIds($section->course_id, $at))->flatMap(fn (int $courseId) => $completed[$courseId] ?? [])
            ->sortByDesc(fn (Enrollment $enrollment) => $enrollment->enrolled_at->getTimestamp())->values();

        if ($earlier->isEmpty()) {
            return RuleCheck::pass();
        }

        $details = ['repeats_enrollment_id' => $earlier->firstOrFail()->id];

        if (! ($parameters['allowed'] ?? true)) {
            return RuleCheck::fail(__('registration.rules.repeat_course.not_allowed'), $details);
        }

        $max = $parameters['max_attempts'] ?? null;

        if ($max !== null && $earlier->count() + 1 > (int) $max) {
            return RuleCheck::fail(__('registration.rules.repeat_course.too_many', ['max' => $max]), $details);
        }

        $below = $parameters['below_grade'] ?? null;

        if ($below !== null && $earlier->contains(fn (Enrollment $enrollment) => $enrollment->is_passed && $this->history->meetsMinimum($enrollment->grade_code, (string) $below))) {
            return RuleCheck::fail(__('registration.rules.repeat_course.grade_too_high', ['grade' => $below]), $details);
        }

        return RuleCheck::pass(__('registration.rules.repeat_course.retake'), $details);
    }

    /**
     * @return list<int>
     */
    private function acceptedCourseIds(int $courseId, CarbonImmutable $at): array
    {
        return $this->accepted[$courseId] ??= $this->history->acceptedCourseIds($courseId, $at);
    }

    /**
     * Completed enrollments for the requisite and repeat rules: passed grades per person (any
     * of their records) and course, and per record and course the enrollments of other terms.
     *
     * @param  Collection<int, StudentRecord>  $students
     * @return array{0: array<int, array<int, list<string|null>>>, 1: array<int, array<int, list<Enrollment>>>}
     */
    private function history(Collection $students, Term $term): array
    {
        $persons = StudentRecord::query()->whereIn('person_id', $students->pluck('person_id')->unique()->values()->all())->pluck('person_id', 'id');
        $passed = [];
        $completed = [];

        foreach (array_chunk($persons->keys()->all(), 10000) as $recordIds) {
            $rows = Enrollment::query()->whereIn('student_record_id', $recordIds)->where('status', EnrollmentStatus::Completed)
                ->get(['id', 'student_record_id', 'term_id', 'course_id', 'grade_code', 'is_passed', 'enrolled_at']);

            foreach ($rows as $enrollment) {
                if ($enrollment->is_passed) {
                    $passed[$persons[$enrollment->student_record_id]][$enrollment->course_id][] = $enrollment->grade_code;
                }

                if ($enrollment->term_id !== $term->id) {
                    $completed[$enrollment->student_record_id][$enrollment->course_id][] = $enrollment;
                }
            }
        }

        return [$passed, $completed];
    }
}
