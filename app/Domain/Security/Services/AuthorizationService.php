<?php

namespace App\Domain\Security\Services;

use App\Domain\Security\Data\PermissionScope;
use App\Domain\Security\Enums\Permission;
use App\Domain\Security\Enums\ScopeType;
use App\Models\Organization\OrgUnit;
use App\Models\Security\Role;
use App\Models\Security\RoleAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Answers which permissions a user has and in which scope.
 *
 * Sources: active role assignments (role + scope + period) and persona roles. Bound as a
 * scoped instance, so grants are computed once per request or job and role changes apply
 * on the next request.
 */
class AuthorizationService
{
    /** @var array<int, array<string, PermissionScope>> */
    private array $grants = [];

    public function __construct(private readonly PersonaResolver $personas) {}

    public function hasPermission(User $user, Permission|string $permission): bool
    {
        return ! $this->scopeFor($user, $permission)->isEmpty();
    }

    public function scopeFor(User $user, Permission|string $permission): PermissionScope
    {
        $name = $permission instanceof Permission ? $permission->value : $permission;

        return $this->grantsFor($user)[$name] ?? PermissionScope::none();
    }

    /**
     * @return list<string>
     */
    public function permissionNames(User $user): array
    {
        return array_keys($this->grantsFor($user));
    }

    public function flush(): void
    {
        $this->grants = [];
        $this->personas->flush();
    }

    /**
     * @return array<string, PermissionScope>
     */
    private function grantsFor(User $user): array
    {
        if (isset($this->grants[$user->id])) {
            return $this->grants[$user->id];
        }

        if (! $user->is_active) {
            return $this->grants[$user->id] = [];
        }

        $grants = [];

        $assigned = RoleAssignment::query()
            ->active()
            ->where('role_assignments.user_id', $user->id)
            ->join('role_has_permissions', 'role_has_permissions.role_id', '=', 'role_assignments.role_id')
            ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
            ->toBase()
            ->get(['permissions.name', 'role_assignments.scope_type', 'role_assignments.scope_id']);

        foreach ($assigned as $row) {
            $scope = PermissionScope::fromAssignment(ScopeType::from($row->scope_type), $row->scope_id);
            $grants[$row->name] = isset($grants[$row->name]) ? $grants[$row->name]->merge($scope) : $scope;
        }

        // An org unit scope covers the unit and everything below it.
        $expanded = [];

        foreach ($grants as $name => $scope) {
            if ($scope->orgUnitIds !== []) {
                $roots = $scope->orgUnitIds;
                sort($roots);
                $key = implode(',', $roots);

                $grants[$name] = new PermissionScope(
                    $scope->isGlobal,
                    $expanded[$key] ??= OrgUnit::descendantIdsOf($roots),
                    $scope->programIds,
                    $scope->includesOwn,
                );
            }
        }

        $personas = $this->personas->personasFor($user);

        if ($personas !== []) {
            $personaPermissions = DB::table('roles')
                ->whereIn('roles.persona', array_map(fn ($persona) => $persona->value, $personas))
                ->join('role_has_permissions', 'role_has_permissions.role_id', '=', 'roles.id')
                ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                ->pluck('permissions.name');

            $own = new PermissionScope(includesOwn: true);

            foreach ($personaPermissions as $name) {
                $grants[$name] = isset($grants[$name]) ? $grants[$name]->merge($own) : $own;
            }
        }

        return $this->grants[$user->id] = $grants;
    }

    /**
     * Active users holding the permission through a role assignment whose scope covers the
     * org unit and program, e.g. the reviewers to notify.
     *
     * @return Collection<int, User>
     */
    public function usersHolding(Permission $permission, int $orgUnitId, ?int $programId = null): Collection
    {
        $userIds = RoleAssignment::query()->active()
            ->join('role_has_permissions', 'role_has_permissions.role_id', '=', 'role_assignments.role_id')
            ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
            ->where('permissions.name', $permission->value)
            ->distinct()->pluck('role_assignments.user_id');

        return User::query()->whereIn('id', $userIds)->where('is_active', true)->get()
            ->filter(fn (User $user) => $this->scopeFor($user, $permission)->covers($orgUnitId, $programId ?? 0))->values();
    }

    /**
     * Roles the user currently holds through assignments, for display.
     *
     * @return list<string>
     */
    public function activeRoleNames(User $user): array
    {
        return Role::query()
            ->whereIn('id', RoleAssignment::query()->active()->where('user_id', $user->id)->select('role_id'))
            ->pluck('name')
            ->all();
    }
}
