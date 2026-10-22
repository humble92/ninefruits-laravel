<?php

namespace App\Domain\Person\Actions;

use App\Domain\Person\Enums\StatusChangeStatus;
use App\Domain\Security\Enums\Permission;
use App\Domain\Security\Services\AuditLogger;
use App\Domain\Security\Services\AuthorizationService;
use App\Models\Person\StudentRecord;
use App\Models\Person\StudentStatusChange;
use App\Models\Person\StudentStatusChangeType;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

/**
 * Staff record a status change directly (e.g. dismissal, or a request received on paper).
 * The change is approved at once by the staff member, who needs the approve permission for
 * the record; a reason is required.
 */
class RecordStatusChange
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly AuthorizationService $authorization,
    ) {}

    public function handle(StudentRecord $record, StudentStatusChangeType $type, CarbonImmutable $effectiveDate, string $reason, User $staff, ?int $termId = null): StudentStatusChange
    {
        $scope = $this->authorization->scopeFor($staff, Permission::StudentStatusChangesApprove);

        if (! $scope->isGlobal && ! in_array($record->org_unit_id, $scope->orgUnitIds, true) && ! in_array($record->program_id, $scope->programIds, true)) {
            throw new AuthorizationException;
        }

        RequestStatusChange::ensureApplies($type, $record);

        return DB::transaction(function () use ($record, $type, $effectiveDate, $reason, $staff, $termId) {
            $change = new StudentStatusChange([
                'student_record_id' => $record->id,
                'status_change_type_id' => $type->id,
                'from_status' => $record->status,
                'to_status' => $type->to_status,
                'effective_date' => $effectiveDate,
                'term_id' => $termId,
                'reason' => $reason,
                'requested_by_id' => $staff->id,
                'requested_at' => CarbonImmutable::now(),
            ]);
            $change->forceFill([
                'status' => StatusChangeStatus::Approved,
                'decided_by_id' => $staff->id,
                'decided_at' => CarbonImmutable::now(),
            ])->save();

            DecideStatusChange::apply($record, $type->to_status, $effectiveDate);

            $this->audit->record('student_status_change.recorded', $change, $reason, [
                'student_record_id' => $record->id,
                'type' => $type->code,
                'from' => $change->from_status->value,
                'to' => $change->to_status->value,
            ], $staff);

            return $change;
        });
    }
}
