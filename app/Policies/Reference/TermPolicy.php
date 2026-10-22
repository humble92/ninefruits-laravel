<?php

namespace App\Policies\Reference;

use App\Domain\Security\Enums\Permission;

class TermPolicy extends ReferenceDataPolicy
{
    protected function managePermission(): Permission
    {
        return Permission::TermsManage;
    }
}
