<?php

namespace App\Domain\Grading\Actions;

use App\Domain\Grading\Enums\EvaluationMethod;
use App\Domain\Grading\Enums\GradeStatus;
use App\Domain\Grading\Enums\RequestStatus;
use App\Domain\Grading\Notifications\EvaluationMethodDecidedNotification;
use App\Domain\Registration\Enums\EnrollmentStatus;
use App\Domain\Security\Services\AuditLogger;
use App\Models\Grading\EvaluationMethodRequest;
use App\Models\Offering\Section;
use App\Models\Registration\Enrollment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Sections are evaluated absolutely unless the instructor asks for relative evaluation
 * (decided 2026-10-23). The instructor requests with a reason before any grade is
 * submitted; the staff who schedule the section approve or reject with a note, and the
 * instructor is told by email. Approval clears draft grades, since they were entered under
 * the other method.
 */
class ManageEvaluationMethodRequests
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function request(Section $section, EvaluationMethod $method, string $reason, User $instructor): EvaluationMethodRequest
    {
        if (! $section->isTaughtBy($instructor)) {
            throw new AuthorizationException;
        }

        if ($method === $section->evaluation_method) {
            throw ValidationException::withMessages(['requested_method' => __('grading.errors.same_method')]);
        }

        if (blank($reason)) {
            throw ValidationException::withMessages(['reason' => __('registration.errors.reason_required')]);
        }

        $this->ensureChangeable($section);

        if (EvaluationMethodRequest::query()->where('section_id', $section->id)->pending()->exists()) {
            throw ValidationException::withMessages(['requested_method' => __('grading.errors.method_request_pending')]);
        }

        try {
            $request = DB::transaction(fn () => EvaluationMethodRequest::query()->create([
                'section_id' => $section->id,
                'requested_method' => $method,
                'reason' => $reason,
                'requested_by_id' => $instructor->id,
                'requested_at' => CarbonImmutable::now(),
            ]));
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['requested_method' => __('grading.errors.method_request_pending')]);
        }

        $this->audit->record('evaluation_method.requested', $request, $reason, ['section_id' => $section->id, 'method' => $method->value], $instructor);

        return $request;
    }

    /**
     * Approval changes the section's method and drops draft grades entered under the old one.
     *
     * @return int draft grades cleared
     */
    public function approve(EvaluationMethodRequest $request, User $staff, ?string $note = null): int
    {
        $this->authorizeDecision($request, $staff);
        $section = $request->section;
        $this->ensureChangeable($section);

        $cleared = DB::transaction(function () use ($request, $section, $staff, $note) {
            $cleared = Enrollment::query()->where('section_id', $section->id)->where('status', EnrollmentStatus::Enrolled)
                ->where('grade_status', GradeStatus::Draft)
                ->update([
                    'grade_status' => GradeStatus::None, 'grade_scale_id' => null, 'grade_code' => null, 'score' => null,
                    'grade_points' => null, 'counts_in_gpa' => null, 'is_passed' => null, 'grade_entered_at' => null, 'grade_entered_by_id' => null,
                ]);
            $section->forceFill(['evaluation_method' => $request->requested_method])->save();
            $request->forceFill(['status' => RequestStatus::Approved, 'decided_by_id' => $staff->id, 'decided_at' => CarbonImmutable::now(), 'decision_note' => $note])->save();

            return $cleared;
        });

        $this->audit->record('evaluation_method.approved', $request, $note, ['section_id' => $section->id, 'method' => $request->requested_method->value, 'drafts_cleared' => $cleared], $staff);
        $request->requestedBy->notify(new EvaluationMethodDecidedNotification($request, $cleared));

        return $cleared;
    }

    public function reject(EvaluationMethodRequest $request, User $staff, string $note): EvaluationMethodRequest
    {
        $this->authorizeDecision($request, $staff);

        if (blank($note)) {
            throw ValidationException::withMessages(['note' => __('registration.errors.reason_required')]);
        }

        $request->forceFill(['status' => RequestStatus::Rejected, 'decided_by_id' => $staff->id, 'decided_at' => CarbonImmutable::now(), 'decision_note' => $note])->save();
        $this->audit->record('evaluation_method.rejected', $request, $note, ['section_id' => $request->section_id], $staff);
        $request->requestedBy->notify(new EvaluationMethodDecidedNotification($request, 0));

        return $request;
    }

    public function cancel(EvaluationMethodRequest $request, User $instructor): EvaluationMethodRequest
    {
        if ($request->requested_by_id !== $instructor->id || $request->status !== RequestStatus::Pending) {
            throw new AuthorizationException;
        }

        $request->forceFill(['status' => RequestStatus::Cancelled, 'decided_at' => CarbonImmutable::now()])->save();
        $this->audit->record('evaluation_method.cancelled', $request, causer: $instructor);

        return $request;
    }

    public function canDecide(EvaluationMethodRequest $request, User $user): bool
    {
        return $request->status === RequestStatus::Pending && $request->section->isManageableBy($user);
    }

    /**
     * Not after a grade is submitted or finalized under the current method.
     */
    private function ensureChangeable(Section $section): void
    {
        if (Enrollment::query()->where('section_id', $section->id)->whereIn('grade_status', [GradeStatus::Submitted, GradeStatus::Finalized])->exists()) {
            throw ValidationException::withMessages(['requested_method' => __('grading.errors.method_has_grades')]);
        }
    }

    private function authorizeDecision(EvaluationMethodRequest $request, User $staff): void
    {
        $request->loadMissing('section');

        if ($request->status !== RequestStatus::Pending) {
            throw ValidationException::withMessages(['status' => __('grading.errors.request_decided')]);
        }

        if (! $request->section->isManageableBy($staff)) {
            throw new AuthorizationException;
        }
    }
}
