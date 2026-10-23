<?php

namespace App\Domain\Registration\Actions;

use App\Domain\Registration\Data\EnrollOptions;
use App\Domain\Registration\Data\RegistrationContext;
use App\Domain\Registration\Data\RegistrationOutcome;
use App\Domain\Registration\Data\RuleEvaluation;
use App\Domain\Registration\Data\RuleResult;
use App\Domain\Registration\Enums\AttemptAction;
use App\Domain\Registration\Enums\AttemptOutcome;
use App\Domain\Registration\Enums\EnrollmentSource;
use App\Domain\Registration\Enums\RuleCode;
use App\Domain\Registration\Enums\RuleSeverity;
use App\Domain\Registration\Enums\WaitlistStatus;
use App\Domain\Registration\Events\EnrollmentConfirmed;
use App\Domain\Registration\Services\AttemptLog;
use App\Domain\Registration\Services\RegistrationRuleEngine;
use App\Domain\Registration\Services\SeatAllocator;
use App\Domain\Security\Services\AuditLogger;
use App\Models\Offering\Section;
use App\Models\Offering\SectionSeatGroup;
use App\Models\Person\StudentRecord;
use App\Models\Registration\CartItem;
use App\Models\Registration\Enrollment;
use App\Models\Registration\WaitlistEntry;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Registers a student for a section: all rules first (every reason is reported), then a
 * seat taken under the section's row lock (docs/porting-plan.md section 10.7). With no
 * seat the student can join the waitlist. Every attempt is logged with its rule results.
 *
 * The student registers themselves; staff with registration.manage for the section may
 * register anyone and waive rules that allow it, with a reason. Waitlist promotion calls
 * this with no actor (system).
 */
class EnrollStudent
{
    public function __construct(
        private readonly RegistrationRuleEngine $engine,
        private readonly SeatAllocator $seats,
        private readonly AttemptLog $attempts,
        private readonly AuditLogger $audit,
    ) {}

    public function handle(StudentRecord $record, Section $section, ?User $actor, ?EnrollOptions $options = null, AttemptAction $action = AttemptAction::Enroll): RegistrationOutcome
    {
        $options ??= new EnrollOptions;
        $byStaff = $this->authorize($record, $section, $actor, $options);
        $at = CarbonImmutable::now();

        $evaluation = $this->engine->evaluate(new RegistrationContext($record, $section, $action, $at, $actor, $byStaff, $options->waive));

        if ($evaluation->isBlocked()) {
            $this->attempts->record($record, $section, $action, AttemptOutcome::Refused, $evaluation, $actor);

            return new RegistrationOutcome(AttemptOutcome::Refused, $evaluation);
        }

        $retakeOf = $evaluation->detail('repeats_enrollment_id');

        try {
            $outcome = DB::transaction(function () use ($record, $section, $actor, $options, $evaluation, $at, $byStaff, $retakeOf) {
                $locked = $this->seats->lock($section->id);
                $seat = $this->seats->find($locked, $record, $at);

                if ($seat === null) {
                    return $this->full($record, $locked, $actor, $options, $evaluation, $at);
                }

                $version = $locked->version;
                $enrollment = Enrollment::query()->create([
                    'student_record_id' => $record->id,
                    'section_id' => $locked->id,
                    'term_id' => $locked->term_id,
                    'course_id' => $locked->course_id,
                    'course_version_id' => $locked->course_version_id,
                    'credits' => $version->credits,
                    'course_category_id' => $version->course_category_id,
                    'counts_toward_credit_limit' => $version->counts_toward_credit_limit,
                    'seat_group_id' => $seat instanceof SectionSeatGroup ? $seat->id : null,
                    'is_retake' => $retakeOf !== null,
                    'repeats_enrollment_id' => $retakeOf,
                    'source' => $options->source ?? ($byStaff ? EnrollmentSource::Staff : EnrollmentSource::Student),
                    'enrolled_at' => $at,
                    'changed_by_id' => $actor?->id,
                ]);
                $this->seats->take($locked, $seat);

                CartItem::query()->where('student_record_id', $record->id)->where('section_id', $locked->id)->delete();
                WaitlistEntry::query()->where('student_record_id', $record->id)->where('section_id', $locked->id)->where('status', WaitlistStatus::Waiting)
                    ->update(['status' => WaitlistStatus::Promoted, 'resolved_at' => $at, 'enrollment_id' => $enrollment->id]);

                EnrollmentConfirmed::dispatch($enrollment);

                return new RegistrationOutcome(AttemptOutcome::Succeeded, $evaluation, $enrollment);
            });
        } catch (UniqueConstraintViolationException) {
            // A parallel request of the same student got there first.
            $outcome = new RegistrationOutcome(AttemptOutcome::Refused, self::with($evaluation, RuleCode::DuplicateCourse, __('registration.rules.duplicate_course.failed', ['section' => $section->label()])));
        }

        $this->attempts->record($record, $section, $outcome->outcome === AttemptOutcome::Waitlisted ? AttemptAction::Waitlist : $action, $outcome->outcome, $outcome->evaluation, $actor);

        if ($byStaff && $outcome->enrollment !== null) {
            $this->audit->record('enrollment.staff_enrolled', $outcome->enrollment, $options->reason, [
                'waived' => array_map(fn (RuleCode $code) => $code->value, $options->waive),
                'warnings' => $outcome->evaluation->warnings(),
            ], $actor);
        }

        return $outcome;
    }

    private function full(StudentRecord $record, Section $section, ?User $actor, EnrollOptions $options, RuleEvaluation $evaluation, CarbonImmutable $at): RegistrationOutcome
    {
        $waiting = WaitlistEntry::query()->where('section_id', $section->id)->where('status', WaitlistStatus::Waiting)->count();

        if (! $options->waitlistIfFull || $section->waitlist_capacity <= $waiting) {
            $message = $section->waitlist_capacity > $waiting ? __('registration.capacity.full_waitlist_open') : __('registration.capacity.full');

            return new RegistrationOutcome(AttemptOutcome::Refused, self::with($evaluation, RuleCode::Capacity, $message));
        }

        $entry = WaitlistEntry::query()->where('section_id', $section->id)->where('student_record_id', $record->id)->where('status', WaitlistStatus::Waiting)->first()
            ?? WaitlistEntry::query()->create(['section_id' => $section->id, 'student_record_id' => $record->id, 'requested_at' => $at, 'requested_by_id' => $actor?->id]);

        return new RegistrationOutcome(AttemptOutcome::Waitlisted, self::with($evaluation, RuleCode::Capacity, __('registration.capacity.waitlisted', ['position' => $entry->position()]), passed: true), waitlistEntry: $entry);
    }

    private static function with(RuleEvaluation $evaluation, RuleCode $code, string $message, bool $passed = false): RuleEvaluation
    {
        return new RuleEvaluation([...$evaluation->results, new RuleResult($code, $passed, RuleSeverity::Block, false, $message)]);
    }

    /**
     * @return bool Whether staff act for the student
     */
    private function authorize(StudentRecord $record, Section $section, ?User $actor, EnrollOptions $options): bool
    {
        if ($actor === null) {
            return false; // system: waitlist promotion
        }

        if ($actor->person_id !== null && $actor->person_id === $record->person_id && $options->waive === []) {
            return false;
        }

        if (! $section->isRegistrationManageableBy($actor)) {
            throw new AuthorizationException;
        }

        if (blank($options->reason)) {
            throw ValidationException::withMessages(['reason' => __('registration.errors.reason_required')]);
        }

        return true;
    }
}
