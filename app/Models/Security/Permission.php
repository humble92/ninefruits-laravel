<?php

namespace App\Models\Security;

use App\Domain\Security\Enums\Permission as PermissionName;
use Spatie\Permission\Models\Permission as SpatiePermission;

/**
 * @property int $id
 * @property string $name
 * @property string $guard_name
 */
class Permission extends SpatiePermission
{
    public function label(): string
    {
        return PermissionName::tryFrom($this->name)?->label() ?? $this->name;
    }
}
