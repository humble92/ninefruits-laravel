<?php

namespace App\Policies\Reference;

use App\Domain\Security\Enums\Permission;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Reference data: anyone with reference_data.view can look, the resource's manage
 * permission is needed to change. Subclasses name that permission.
 */
abstract class ReferenceDataPolicy
{
    abstract protected function managePermission(): Permission;

    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ReferenceDataView->value) || $this->canManage($user);
    }

    public function view(User $user, Model $model): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, Model $model): bool
    {
        return $this->canManage($user);
    }

    public function delete(User $user, Model $model): bool
    {
        return $this->canManage($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->canManage($user);
    }

    private function canManage(User $user): bool
    {
        return $user->can($this->managePermission()->value);
    }
}
