<?php

namespace App\Policies\Curriculum;

use App\Domain\Security\Enums\Permission;
use App\Policies\Reference\ReferenceDataPolicy;

/**
 * Graduation requirement sets and their lines.
 */
class CurriculumPolicy extends ReferenceDataPolicy
{
    protected function managePermission(): Permission
    {
        return Permission::CurriculumManage;
    }
}
