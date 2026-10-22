<?php

namespace App\Policies\Reference;

use App\Domain\Security\Enums\Permission;

class FacilityPolicy extends ReferenceDataPolicy
{
    protected function managePermission(): Permission
    {
        return Permission::FacilitiesManage;
    }
}
