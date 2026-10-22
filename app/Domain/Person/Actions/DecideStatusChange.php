<?php

namespace App\Domain\Person\Actions;

use App\Domain\Person\Enums\StatusChangeStatus;
use App\Domain\Person\Enums\StudentStatus;
use App\Domain\Security\Enums\Permission;
use App\Domain\Security\Services\AuditLogger;
use App\Domain\Security\Services\AuthorizationService;
use App\Models\Person\StudentRecord;
use App\Models\Person\StudentStatusChange;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Approves or rejects a requested status change. Approval applies the new status to the
 * record. The requester can never decide their own request (separation of duties).
 */
class DecideStatusChange
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly AuthorizationService $authorization,
    ) {}

    public function approve(StudentStatusChange $change, User $approver, ?string $note = null): StudentStatusChange
    {
        return $this->decide($change, $approver, StatusChangeStatus::Approved, $note);
    }

    public function reject(StudentStatusChange $change, User $approver, string $note): StudentStatusChange
    {
        return $this->decide($change, $approver, StatusChangeStatus::Rejected, $note);
    }

    private function decide(StudentStatusChange $change, User $approver, StatusChangeStatus $decision, ?string $note): StudentStatusChange
    {
        if ($change->status !== StatusChangeStatus::Requested) {
            throw ValidationException::withMessages(['status' => __('people.errors.already_decided')]);
        }

        if ($change->requested_by_id === $approver->id) {
            throw new AuthorizationException(__('people.errors.own_request'));
        }

        $record = $change->studentRecord()->firstOrFail();
        $scope = $this->authorization->scopeFor($approver, Permission::StudentStatusChangesApprove);

        if (! $scope->isGlobal && ! in_array($record->org_unit_id, $scope->orgUnitIds, true) && ! in_array($record->program_id, $scope->programIds, true)) {
            throw new AuthorizationException;
        }

        return DB::transaction(function () use ($change, $approver, $decision, $note, $record) {
            if ($decision === StatusChangeStatus::Approved) {
                if ($record->status !== $change->from_status) {
                    throw ValidationException::withMessages(['status' => __('people.errors.status_changed_meanwhile')]);
                }

                self::apply($record, $change->to_status, $change->effective_date);
            }

            $change->forceFill([
                'status' => $decision,
                'decided_by_id' => $approver->id,
                'decided_at' => CarbonImmutable::now(),
                'decision_note' => $note,
            ])->save();

            $this->audit->record('student_status_change.'.$decision->value, $change, $note, [
                'student_record_id' => $record->id,
                'from' => $change->from_status->value,
                'to' => $change->to_status->value,
            ], $approver);

            return $change;
        });
    }

    /**
     * Sets the record status. Graduation also needs the graduation date.
     */
    public static function apply(StudentRecord $record, StudentStatus $status, CarbonImmutable $effectiveDate): void
    {
        $record->forceFill([
            'status' => $status,
            'status_changed_at' => CarbonImmutable::now(),
            'graduation_date' => $status === StudentStatus::Graduated ? $effectiveDate : $record->graduation_date,
        ])->save();
    }
}
