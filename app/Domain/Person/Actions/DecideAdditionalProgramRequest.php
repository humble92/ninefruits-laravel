<?php

namespace App\Domain\Person\Actions;

use App\Domain\Person\Enums\AdditionalProgramRequestStatus;
use App\Domain\Person\Settings\StudentRecordSettings;
use App\Domain\Security\Services\AuditLogger;
use App\Models\Person\AdditionalProgramRequest;
use App\Models\Person\StudentRecordProgram;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Staff of the target program (additional_programs.approve in its scope) approve or reject
 * a second major or minor request. Approval adds the program to the student record from
 * the start of the request's term.
 */
class DecideAdditionalProgramRequest
{
    public function __construct(
        private readonly StudentRecordSettings $settings,
        private readonly AuditLogger $audit,
    ) {}

    public function approve(AdditionalProgramRequest $request, User $approver, ?string $note = null): StudentRecordProgram
    {
        $this->ensureDecidable($request, $approver);
        RequestAdditionalProgram::ensureAllowed($request->studentRecord, $request->program, $request->role, $this->settings, ignoreRequestId: $request->id);

        return DB::transaction(function () use ($request, $approver, $note) {
            $program = StudentRecordProgram::query()->create([
                'student_record_id' => $request->student_record_id,
                'program_id' => $request->program_id,
                'role' => $request->role,
                'start_date' => $request->term->start_date,
            ]);

            $request->forceFill([
                'status' => AdditionalProgramRequestStatus::Approved,
                'decided_by_id' => $approver->id,
                'decided_at' => CarbonImmutable::now(),
                'decision_note' => $note,
                'student_record_program_id' => $program->id,
            ])->save();

            $this->audit->record('additional_program.approved', $request, $note, ['student_record_program_id' => $program->id], $approver);

            return $program;
        });
    }

    public function reject(AdditionalProgramRequest $request, User $approver, string $note): AdditionalProgramRequest
    {
        $this->ensureDecidable($request, $approver);

        $request->forceFill([
            'status' => AdditionalProgramRequestStatus::Rejected,
            'decided_by_id' => $approver->id,
            'decided_at' => CarbonImmutable::now(),
            'decision_note' => $note,
        ])->save();

        $this->audit->record('additional_program.rejected', $request, $note, causer: $approver);

        return $request;
    }

    private function ensureDecidable(AdditionalProgramRequest $request, User $approver): void
    {
        if (! $request->isDecidableBy($approver)) {
            throw new AuthorizationException;
        }

        if ($request->status !== AdditionalProgramRequestStatus::Requested) {
            throw ValidationException::withMessages(['status' => __('people.errors.already_decided')]);
        }

        if ($request->requested_by_id === $approver->id) {
            throw new AuthorizationException(__('people.errors.own_request'));
        }
    }
}
