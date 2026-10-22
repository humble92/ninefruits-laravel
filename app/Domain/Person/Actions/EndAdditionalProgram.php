<?php

namespace App\Domain\Person\Actions;

use App\Domain\Security\Services\AuditLogger;
use App\Models\Person\StudentRecordProgram;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

/**
 * Ends a second major or minor (given up, or not completed). The registrar (records in
 * scope) or staff of the program (approval scope) do this with a reason.
 */
class EndAdditionalProgram
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function handle(StudentRecordProgram $program, CarbonImmutable $endDate, string $reason, User $user): StudentRecordProgram
    {
        $record = $program->studentRecord;

        if (! $record->isManageableBy($user) && ! $program->program->isAdditionalProgramDecidableBy($user)) {
            throw new AuthorizationException;
        }

        if ($program->end_date !== null || $endDate->lessThan($program->start_date)) {
            throw ValidationException::withMessages(['end_date' => __('people.errors.additional_program_end_invalid')]);
        }

        $program->update(['end_date' => $endDate]);
        $this->audit->record('additional_program.ended', $program, $reason, ['student_record_id' => $record->id], $user);

        return $program;
    }
}
