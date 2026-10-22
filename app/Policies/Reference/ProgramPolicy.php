<?php

namespace App\Policies\Reference;

use App\Domain\Security\Enums\Permission;

class ProgramPolicy extends ReferenceDataPolicy
{
    protected function managePermission(): Permission
    {
        return Permission::ProgramsManage;
    }
}
