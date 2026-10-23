<?php

namespace App\Domain\Grading\Actions;

use App\Domain\Grading\Enums\GradeChangeStatus;
use App\Domain\Grading\Enums\GradeStatus;
use App\Domain\Grading\Services\GradeOptions;
use App\Domain\Grading\Services\TermRecordCalculator;
use App\Domain\Security\Enums\Permission;
use App\Domain\Security\Services\AuditLogger;
use App\Models\Grading\GradeChangeRequest;
use App\Models\Registration\Enrollment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Changing a finalized grade (docs/porting-plan.md section 8, 17.2): the section's
 * instructor or staff with grade_changes.request ask with a reason; someone else with
 * grade_changes.approve for the section decides. Approval applies the change in the same
 * transaction, the only way the database lets a finalized grade change.
 */
class ManageGradeChanges
{
    public function __construct(
        private readonly GradeOptions $options,
        private readonly TermRecordCalculator $records,
        private readonly AuditLogger $audit,
    ) {}

    public function request(Enrollment $enrollment, string $toGrade, string $reason, User $requester, ?string $toScore = null): GradeChangeRequest
    {
        $enrollment->loadMissing(['section', 'version']);

        if (! $enrollment->section->isTaughtBy($requester) && ! $enrollment->section->isInScopeOf($requester, Permission::GradeChangesRequest)) {
            throw new AuthorizationException;
        }

        if ($enrollment->grade_status !== GradeStatus::Finalized) {
            throw ValidationException::withMessages(['grade' => __('grading.errors.not_finalized')]);
        }

        if (blank($reason)) {
            throw ValidationException::withMessages(['reason' => __('registration.errors.reason_required')]);
        }

        $item = $this->options->resolve($enrollment, $toGrade, null);

        if ($item?->code === $enrollment->grade_code && $toScore === $enrollment->score) {
            throw ValidationException::withMessages(['to_grade_code' => __('grading.errors.same_grade')]);
        }

        if (GradeChangeRequest::query()->where('enrollment_id', $enrollment->id)->pending()->exists()) {
            throw ValidationException::withMessages(['to_grade_code' => __('grading.errors.request_pending')]);
        }

        try {
            // A savepoint, so a concurrent duplicate does not abort the caller's transaction.
            $request = DB::transaction(fn () => GradeChangeRequest::query()->create([
                'enrollment_id' => $enrollment->id,
                'from_grade_code' => $enrollment->grade_code,
                'to_grade_code' => $toGrade,
                'to_score' => $toScore,
                'reason' => $reason,
                'requested_by_id' => $requester->id,
                'requested_at' => CarbonImmutable::now(),
            ]));
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['to_grade_code' => __('grading.errors.request_pending')]);
        }

        $this->audit->record('grade_change.requested', $request, $reason, ['from' => $enrollment->grade_code, 'to' => $toGrade], $requester);

        return $request;
    }

    public function approve(GradeChangeRequest $request, User $approver, ?string $note = null): GradeChangeRequest
    {
        $this->authorizeDecision($request, $approver);

        DB::transaction(function () use ($request, $approver, $note) {
            $enrollment = Enrollment::query()->with('version')->lockForUpdate()->findOrFail($request->enrollment_id);
            $item = $this->options->resolve($enrollment, $request->to_grade_code, null)
                ?? throw ValidationException::withMessages(['to_grade_code' => __('grading.errors.grade_not_allowed', ['grade' => $request->to_grade_code])]);

            $request->forceFill(['status' => GradeChangeStatus::Approved, 'decided_by_id' => $approver->id, 'decided_at' => CarbonImmutable::now(), 'decision_note' => $note])->save();

            // The guard trigger lets the finalized grade change for this approved request only.
            DB::select("SELECT set_config('app.grade_change_request_id', ?, true)", [(string) $request->id]);
            $enrollment->forceFill([
                'grade_scale_id' => $item->grade_scale_id,
                'grade_code' => $item->code,
                'score' => $request->to_score,
                'grade_points' => $item->grade_points,
                'counts_in_gpa' => $item->counts_in_gpa,
                'is_passed' => $item->is_passing,
            ])->save();
            DB::select("SELECT set_config('app.grade_change_request_id', '', true)");

            $request->forceFill(['applied_at' => CarbonImmutable::now()])->save();
            $this->audit->record('grade_change.approved', $request, $request->reason, [
                'enrollment_id' => $enrollment->id, 'from' => $request->from_grade_code, 'to' => $request->to_grade_code, 'note' => $note,
            ], $approver);
        });

        $enrollment = $request->enrollment()->with(['studentRecord', 'term'])->firstOrFail();
        $this->records->recalculate($enrollment->studentRecord, $enrollment->term);

        return $request->refresh();
    }

    public function reject(GradeChangeRequest $request, User $approver, string $note): GradeChangeRequest
    {
        $this->authorizeDecision($request, $approver);

        if (blank($note)) {
            throw ValidationException::withMessages(['note' => __('registration.errors.reason_required')]);
        }

        $request->forceFill(['status' => GradeChangeStatus::Rejected, 'decided_by_id' => $approver->id, 'decided_at' => CarbonImmutable::now(), 'decision_note' => $note])->save();
        $this->audit->record('grade_change.rejected', $request, $note, causer: $approver);

        return $request;
    }

    public function cancel(GradeChangeRequest $request, User $requester): GradeChangeRequest
    {
        if ($request->requested_by_id !== $requester->id || $request->status !== GradeChangeStatus::Pending) {
            throw new AuthorizationException;
        }

        $request->forceFill(['status' => GradeChangeStatus::Cancelled, 'decided_at' => CarbonImmutable::now()])->save();
        $this->audit->record('grade_change.cancelled', $request, causer: $requester);

        return $request;
    }

    public function canDecide(GradeChangeRequest $request, User $user): bool
    {
        return $request->status === GradeChangeStatus::Pending && $request->requested_by_id !== $user->id
            && $request->enrollment->section->isInScopeOf($user, Permission::GradeChangesApprove);
    }

    private function authorizeDecision(GradeChangeRequest $request, User $approver): void
    {
        $request->loadMissing('enrollment.section');

        if ($request->status !== GradeChangeStatus::Pending) {
            throw ValidationException::withMessages(['status' => __('grading.errors.request_decided')]);
        }

        // Separation of duties (also a database constraint).
        if ($request->requested_by_id === $approver->id) {
            throw ValidationException::withMessages(['status' => __('grading.errors.own_request')]);
        }

        if (! $request->enrollment->section->isInScopeOf($approver, Permission::GradeChangesApprove)) {
            throw new AuthorizationException;
        }
    }
}
