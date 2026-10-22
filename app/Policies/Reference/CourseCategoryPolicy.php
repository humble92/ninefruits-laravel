<?php

namespace App\Policies\Reference;

use App\Domain\Security\Enums\Permission;

class CourseCategoryPolicy extends ReferenceDataPolicy
{
    protected function managePermission(): Permission
    {
        return Permission::CourseCategoriesManage;
    }
}
