<?php

namespace App\Domain\Person\Actions;

use App\Domain\Person\Enums\AdditionalProgramRequestStatus;
use App\Domain\Security\Services\AuditLogger;
use App\Models\Person\AdditionalProgramRequest;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

class CancelAdditionalProgramRequest
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function handle(AdditionalProgramRequest $request, User $student): AdditionalProgramRequest
    {
        if ($request->studentRecord->person_id !== $student->person_id) {
            throw new AuthorizationException;
        }

        if ($request->status !== AdditionalProgramRequestStatus::Requested) {
            throw ValidationException::withMessages(['status' => __('people.errors.already_decided')]);
        }

        $request->forceFill(['status' => AdditionalProgramRequestStatus::Cancelled])->save();
        $this->audit->record('additional_program.request_cancelled', $request, causer: $student);

        return $request;
    }
}
