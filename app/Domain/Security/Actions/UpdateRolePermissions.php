<?php

namespace App\Domain\Security\Actions;

use App\Domain\Security\Enums\SecurityEventType;
use App\Domain\Security\Services\AuditLogger;
use App\Domain\Security\Services\AuthorizationService;
use App\Models\Security\Permission;
use App\Models\Security\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateRolePermissions
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly RecordSecurityEvent $recordSecurityEvent,
        private readonly AuthorizationService $authorization,
    ) {}

    /**
     * @param  list<string>  $permissionNames
     */
    public function handle(Role $role, array $permissionNames, ?User $changedBy = null, ?string $reason = null): Role
    {
        $before = $role->permissions()->pluck('name')->sort()->values()->all();
        $after = Permission::query()->whereIn('name', $permissionNames)->pluck('name')->sort()->values()->all();

        if ($before === $after) {
            return $role;
        }

        DB::transaction(function () use ($role, $before, $after, $changedBy, $reason) {
            $role->syncPermissions($after);

            $properties = [
                'added' => array_values(array_diff($after, $before)),
                'removed' => array_values(array_diff($before, $after)),
            ];

            $this->audit->record('role.permissions_changed', $role, $reason, $properties, $changedBy, 'security');
            $this->recordSecurityEvent->handle(SecurityEventType::RolePermissionsChanged, $changedBy, subject: $role, properties: $properties);
        });

        $this->authorization->flush();

        return $role;
    }
}
