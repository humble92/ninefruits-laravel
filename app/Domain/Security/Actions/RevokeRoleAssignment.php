<?php

namespace App\Domain\Security\Actions;

use App\Domain\Security\Enums\SecurityEventType;
use App\Domain\Security\Services\AuditLogger;
use App\Domain\Security\Services\AuthorizationService;
use App\Models\Security\RoleAssignment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Ends an assignment now and keeps the row as history. An assignment that has not started
 * yet is deleted (the audit trigger keeps its record).
 */
class RevokeRoleAssignment
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly RecordSecurityEvent $recordSecurityEvent,
        private readonly AuthorizationService $authorization,
    ) {}

    public function handle(RoleAssignment $assignment, string $reason, ?User $revokedBy = null): RoleAssignment
    {
        $now = CarbonImmutable::now();

        DB::transaction(function () use ($assignment, $reason, $revokedBy, $now) {
            $properties = ['user_id' => $assignment->user_id, 'role_id' => $assignment->role_id];

            if ($assignment->starts_at !== null && $assignment->starts_at->greaterThanOrEqualTo($now)) {
                $assignment->delete();
            } else {
                $assignment->update(['ends_at' => $now]);
            }

            $this->audit->record('role.assignment_revoked', $assignment, $reason, $properties, $revokedBy, 'security');
            $this->recordSecurityEvent->handle(SecurityEventType::RoleAssignmentRevoked, $revokedBy, subject: $assignment, properties: $properties);
        });

        $this->authorization->flush();

        return $assignment;
    }
}
