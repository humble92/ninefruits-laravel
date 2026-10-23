<?php

namespace App\Domain\Assessment\Actions;

use App\Domain\Assessment\Enums\CorrectionStatus;
use App\Domain\Security\Enums\Permission;
use App\Domain\Security\Services\AuditLogger;
use App\Models\Assessment\AssessmentResult;
use App\Models\Assessment\AssessmentResultCorrection;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Corrections of published results (decided 2026-10-23): staff who record results request
 * with the new values and a reason; someone else with assessments.manage approves (which
 * applies the values) or rejects with a note. Like grade changes.
 */
class CorrectAssessmentResult
{
    public function __construct(private readonly AuditLogger $audit, private readonly RecordAssessmentResult $results) {}

    /**
     * @param  array{scores?: array<string, mixed>, total?: float|null, passed?: bool|null, note?: string|null}  $proposed
     */
    public function request(AssessmentResult $result, array $proposed, string $reason, User $staff): AssessmentResultCorrection
    {
        if (! $staff->can(Permission::AssessmentsRecord->value)) {
            throw new AuthorizationException;
        }

        $result->loadMissing(['session', 'componentScores.component', 'registration']);

        if (! $result->isLocked()) {
            throw ValidationException::withMessages(['result' => __('assessment.errors.not_locked')]);
        }

        if (blank($reason)) {
            throw ValidationException::withMessages(['reason' => __('registration.errors.reason_required')]);
        }

        try {
            $correction = DB::transaction(fn () => AssessmentResultCorrection::query()->create([
                'assessment_result_id' => $result->id, 'proposed' => $proposed, 'previous' => $result->values(), 'reason' => $reason,
                'requested_by_id' => $staff->id, 'requested_at' => CarbonImmutable::now(),
            ]));
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['result' => __('assessment.errors.correction_pending')]);
        }

        $this->audit->record('assessment.correction_requested', $correction, $reason, ['result_id' => $result->id, 'proposed' => $proposed], $staff);

        return $correction;
    }

    public function approve(AssessmentResultCorrection $correction, User $approver, ?string $note = null): AssessmentResultCorrection
    {
        $this->authorizeDecision($correction, $approver);
        $result = $correction->result->load('registration');
        $proposed = $correction->proposed;

        DB::transaction(function () use ($correction, $result, $proposed, $approver, $note) {
            $this->results->forRegistration(
                $result->registration ?? throw ValidationException::withMessages(['result' => __('assessment.errors.not_locked')]),
                $proposed['scores'] ?? [], $proposed['passed'] ?? null, $approver, isset($proposed['total']) ? (float) $proposed['total'] : null, $proposed['note'] ?? null,
                applyingCorrection: true,
            );
            $correction->forceFill(['status' => CorrectionStatus::Approved, 'decided_by_id' => $approver->id, 'decided_at' => CarbonImmutable::now(), 'decision_note' => $note])->save();
        });

        $this->audit->record('assessment.correction_approved', $correction, $note, ['result_id' => $result->id], $approver);

        return $correction;
    }

    public function reject(AssessmentResultCorrection $correction, User $approver, string $note): AssessmentResultCorrection
    {
        $this->authorizeDecision($correction, $approver);

        if (blank($note)) {
            throw ValidationException::withMessages(['note' => __('registration.errors.reason_required')]);
        }

        $correction->forceFill(['status' => CorrectionStatus::Rejected, 'decided_by_id' => $approver->id, 'decided_at' => CarbonImmutable::now(), 'decision_note' => $note])->save();
        $this->audit->record('assessment.correction_rejected', $correction, $note, ['result_id' => $correction->assessment_result_id], $approver);

        return $correction;
    }

    public function cancel(AssessmentResultCorrection $correction, User $requester): AssessmentResultCorrection
    {
        if ($correction->requested_by_id !== $requester->id || $correction->status !== CorrectionStatus::Pending) {
            throw new AuthorizationException;
        }

        $correction->forceFill(['status' => CorrectionStatus::Cancelled, 'decided_at' => CarbonImmutable::now()])->save();
        $this->audit->record('assessment.correction_cancelled', $correction, causer: $requester);

        return $correction;
    }

    private function authorizeDecision(AssessmentResultCorrection $correction, User $approver): void
    {
        if (! $approver->can(Permission::AssessmentsManage->value)) {
            throw new AuthorizationException;
        }

        if ($correction->status !== CorrectionStatus::Pending) {
            throw ValidationException::withMessages(['status' => __('grading.errors.request_decided')]);
        }

        if ($correction->requested_by_id === $approver->id) {
            throw ValidationException::withMessages(['status' => __('assessment.errors.own_correction')]);
        }
    }
}
