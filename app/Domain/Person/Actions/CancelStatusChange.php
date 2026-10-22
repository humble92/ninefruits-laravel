<?php

namespace App\Domain\Person\Actions;

use App\Domain\Person\Enums\StatusChangeStatus;
use App\Domain\Security\Services\AuditLogger;
use App\Models\Person\StudentStatusChange;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

/**
 * The requester withdraws a pending request.
 */
class CancelStatusChange
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function handle(StudentStatusChange $change, User $user): StudentStatusChange
    {
        if ($change->requested_by_id !== $user->id) {
            throw new AuthorizationException;
        }

        if ($change->status !== StatusChangeStatus::Requested) {
            throw ValidationException::withMessages(['status' => __('people.errors.already_decided')]);
        }

        $change->forceFill(['status' => StatusChangeStatus::Cancelled, 'decided_at' => CarbonImmutable::now()])->save();

        $this->audit->record('student_status_change.cancelled', $change, null, [], $user);

        return $change;
    }
}
