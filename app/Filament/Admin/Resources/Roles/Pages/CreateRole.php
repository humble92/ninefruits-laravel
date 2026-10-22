<?php

namespace App\Filament\Admin\Resources\Roles\Pages;

use App\Domain\Security\Actions\UpdateRolePermissions;
use App\Filament\Admin\Resources\Roles\RoleResource;
use App\Filament\Admin\Resources\Roles\Schemas\RoleForm;
use App\Models\Security\Role;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $permissions = RoleForm::flattenPermissions($data['permissions'] ?? []);

        $role = Role::query()->create([
            'name' => $data['name'],
            'guard_name' => 'web',
            'display_name' => array_filter($data['display_name']),
            'description' => array_filter($data['description'] ?? []) ?: null,
        ]);

        /** @var User $actor */
        $actor = Auth::user();

        return app(UpdateRolePermissions::class)->handle($role, $permissions, $actor);
    }
}
