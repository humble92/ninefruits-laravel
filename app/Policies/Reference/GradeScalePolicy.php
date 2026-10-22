<?php

namespace App\Policies\Reference;

use App\Domain\Security\Enums\Permission;

class GradeScalePolicy extends ReferenceDataPolicy
{
    protected function managePermission(): Permission
    {
        return Permission::GradeScalesManage;
    }
}
