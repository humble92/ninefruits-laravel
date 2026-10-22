<?php

namespace App\Policies\Reference;

use App\Domain\Security\Enums\Permission;

class OrgUnitPolicy extends ReferenceDataPolicy
{
    protected function managePermission(): Permission
    {
        return Permission::OrgUnitsManage;
    }
}
