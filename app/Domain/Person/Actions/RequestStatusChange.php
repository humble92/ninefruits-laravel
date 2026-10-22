<?php

namespace App\Domain\Person\Actions;

use App\Domain\Security\Services\AuditLogger;
use App\Models\Person\StudentRecord;
use App\Models\Person\StudentStatusChange;
use App\Models\Person\StudentStatusChangeType;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * A student asks for a status change (e.g. leave of absence) of their own record. Staff
 * decide it with DecideStatusChange.
 */
class RequestStatusChange
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function handle(StudentRecord $record, StudentStatusChangeType $type, CarbonImmutable $effectiveDate, string $reason, User $student, ?int $termId = null): StudentStatusChange
    {
        if ($record->person_id !== $student->person_id) {
            throw ValidationException::withMessages(['student_record_id' => __('people.errors.not_own_record')]);
        }

        if (! $type->can_student_request || ! $type->is_active) {
            throw ValidationException::withMessages(['status_change_type_id' => __('people.errors.not_requestable')]);
        }

        self::ensureApplies($type, $record);

        try {
            // Own savepoint: on PostgreSQL a failed statement would abort the caller's transaction.
            $change = DB::transaction(fn () => StudentStatusChange::query()->create([
                'student_record_id' => $record->id,
                'status_change_type_id' => $type->id,
                'from_status' => $record->status,
                'to_status' => $type->to_status,
                'effective_date' => $effectiveDate,
                'term_id' => $termId,
                'reason' => $reason,
                'requested_by_id' => $student->id,
                'requested_at' => CarbonImmutable::now(),
            ]));
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['status_change_type_id' => __('people.errors.request_pending')]);
        }

        $this->audit->record('student_status_change.requested', $change, $reason, ['type' => $type->code], $student);

        return $change;
    }

    public static function ensureApplies(StudentStatusChangeType $type, StudentRecord $record): void
    {
        if (! $type->appliesFrom($record->status)) {
            throw ValidationException::withMessages(['status_change_type_id' => __('people.errors.type_not_applicable', ['status' => $record->status->label()])]);
        }
    }
}
