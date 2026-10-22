<?php

namespace Database\Factories\Security;

use App\Domain\Security\Enums\Permission;
use App\Domain\Security\Enums\Persona;
use App\Models\Security\Permission as PermissionModel;
use App\Models\Security\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    protected $model = Role::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::snake(fake()->unique()->words(2, true));

        return [
            'name' => $name,
            'guard_name' => 'web',
            'display_name' => ['en' => Str::headline($name)],
        ];
    }

    public function persona(Persona $persona): static
    {
        return $this->state(fn () => ['name' => $persona->value, 'persona' => $persona]);
    }

    /**
     * @param  list<Permission>  $permissions
     */
    public function withPermissions(array $permissions): static
    {
        return $this->afterCreating(function (Role $role) use ($permissions) {
            $role->syncPermissions(array_map(
                fn (Permission $permission) => PermissionModel::findOrCreate($permission->value, 'web'),
                $permissions,
            ));
        });
    }
}
