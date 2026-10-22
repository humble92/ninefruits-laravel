<?php

namespace App\Domain\Security\Actions;

use App\Domain\Security\Enums\Permission as PermissionName;
use App\Models\Security\Permission;
use App\Models\Security\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Makes the permissions table match the catalog enum and gives system roles every permission.
 */
class SyncPermissionCatalog
{
    /**
     * What handle() would change, without changing anything.
     *
     * @return array{created: list<string>, deleted: list<string>}
     */
    public function plan(): array
    {
        $catalog = array_map(fn (PermissionName $permission) => $permission->value, PermissionName::cases());
        $existing = Permission::query()->where('guard_name', config('auth.defaults.guard'))->pluck('name')->all();

        return [
            'created' => array_values(array_diff($catalog, $existing)),
            'deleted' => array_values(array_diff($existing, $catalog)),
        ];
    }

    /**
     * @return array{created: list<string>, deleted: list<string>}
     */
    public function handle(): array
    {
        $guard = config('auth.defaults.guard');
        ['created' => $created, 'deleted' => $deleted] = $this->plan();

        foreach ($created as $name) {
            Permission::query()->create(['name' => $name, 'guard_name' => $guard]);
        }

        Permission::query()->where('guard_name', $guard)->whereIn('name', $deleted)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::query()->where('is_system', true)->each(
            fn (Role $role) => $role->syncPermissions(Permission::query()->where('guard_name', $guard)->get())
        );

        return ['created' => $created, 'deleted' => $deleted];
    }
}
