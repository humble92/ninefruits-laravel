<?php

namespace App\Policies\Reference;

use App\Domain\Security\Enums\Permission;

class LookupDataPolicy extends ReferenceDataPolicy
{
    protected function managePermission(): Permission
    {
        return Permission::LookupValuesManage;
    }
}
