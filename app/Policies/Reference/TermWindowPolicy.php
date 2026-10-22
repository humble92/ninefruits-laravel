<?php

namespace App\Policies\Reference;

use App\Domain\Security\Enums\Permission;

class TermWindowPolicy extends ReferenceDataPolicy
{
    protected function managePermission(): Permission
    {
        return Permission::TermWindowsManage;
    }
}
