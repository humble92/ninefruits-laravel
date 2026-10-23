<?php

namespace App\Policies\Assessment;

use App\Domain\Security\Enums\Permission;
use App\Policies\Reference\ReferenceDataPolicy;

/**
 * Examination kinds, their areas and sessions.
 */
class AssessmentSetupPolicy extends ReferenceDataPolicy
{
    protected function managePermission(): Permission
    {
        return Permission::AssessmentsManage;
    }
}
