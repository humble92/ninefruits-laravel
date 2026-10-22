<?php

namespace App\Policies\Curriculum;

use App\Domain\Security\Enums\Permission;
use App\Policies\Reference\ReferenceDataPolicy;

/**
 * Courses, their versions, requisites and equivalencies.
 */
class CoursePolicy extends ReferenceDataPolicy
{
    protected function managePermission(): Permission
    {
        return Permission::CoursesManage;
    }
}
