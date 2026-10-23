<?php

namespace App\Domain\Security\Actions;

use App\Domain\Security\Enums\Permission;
use App\Domain\Security\Enums\ScopeType;
use App\Domain\Security\Enums\SecurityEventType;
use App\Domain\Security\Services\AuditLogger;
use App\Domain\Security\Services\AuthorizationService;
use App\Models\Organization\OrgUnit;
use App\Models\Security\Role;
use App\Models\Security\RoleAssignment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssignRole
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly RecordSecurityEvent $recordSecurityEvent,
        private readonly AuthorizationService $authorization,
    ) {}

    public function handle(
        User $user,
        Role $role,
        string $reason,
        ScopeType $scopeType = ScopeType::Global,
        ?int $scopeId = null,
        ?CarbonImmutable $startsAt = null,
        ?CarbonImmutable $endsAt = null,
        ?User $assignedBy = null,
        bool $enforceActorLimits = true,
    ): RoleAssignment {
        if ($role->isPersona()) {
            throw ValidationException::withMessages(['role_id' => __('security.errors.persona_role_not_assignable')]);
        }

        if (($scopeType === ScopeType::Global) !== ($scopeId === null)) {
            throw ValidationException::withMessages(['scope_id' => __('security.errors.scope_id_mismatch')]);
        }

        if ($startsAt !== null && $endsAt !== null && $endsAt->lessThanOrEqualTo($startsAt)) {
            throw ValidationException::withMessages(['ends_at' => __('security.errors.invalid_period')]);
        }

        if ($assignedBy !== null && $enforceActorLimits) {
            $this->guardAgainstEscalation($assignedBy, $user, $role, $scopeType, $scopeId);
        }

        $assignment = DB::transaction(function () use ($user, $role, $reason, $scopeType, $scopeId, $startsAt, $endsAt, $assignedBy) {
            $assignment = RoleAssignment::query()->create([
                'user_id' => $user->id,
                'role_id' => $role->id,
                'scope_type' => $scopeType,
                'scope_id' => $scopeId,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'reason' => $reason,
                'assigned_by_id' => $assignedBy?->id,
            ]);

            $properties = [
                'user_id' => $user->id,
                'role' => $role->name,
                'scope_type' => $scopeType->value,
                'scope_id' => $scopeId,
                'starts_at' => $startsAt?->toIso8601String(),
                'ends_at' => $endsAt?->toIso8601String(),
            ];

            $this->audit->record('role.assigned', $assignment, $reason, $properties, $assignedBy, 'security');
            $this->recordSecurityEvent->handle(SecurityEventType::RoleAssigned, $assignedBy, subject: $assignment, properties: $properties);

            return $assignment;
        });

        $this->authorization->flush();

        return $assignment;
    }

    /**
     * Nobody grants themselves a role, a role with permissions they do not hold, or a scope
     * wider than their own role_assignments.manage scope (security review 2026-10-23, H3).
     */
    private function guardAgainstEscalation(User $actor, User $user, Role $role, ScopeType $scopeType, ?int $scopeId): void
    {
        if ($actor->id === $user->id) {
            throw ValidationException::withMessages(['user_id' => __('security.errors.self_assignment')]);
        }

        $missing = $role->permissions()->pluck('name')->reject(fn (string $permission) => $actor->can($permission));

        if ($missing->isNotEmpty()) {
            throw ValidationException::withMessages(['role_id' => __('security.errors.role_exceeds_actor', ['permissions' => $missing->take(3)->implode(', ')])]);
        }

        $scope = $this->authorization->scopeFor($actor, Permission::RoleAssignmentsManage);

        if ($scope->isGlobal) {
            return;
        }

        $allowed = match ($scopeType) {
            ScopeType::Global => false,
            ScopeType::OrgUnit => in_array($scopeId, $scope->orgUnitIds, true) || in_array($scopeId, OrgUnit::descendantIdsOf($scope->orgUnitIds), true),
            ScopeType::Program => in_array($scopeId, $scope->programIds, true),
        };

        if (! $allowed) {
            throw ValidationException::withMessages(['scope_id' => __('security.errors.scope_exceeds_actor')]);
        }
    }
}
