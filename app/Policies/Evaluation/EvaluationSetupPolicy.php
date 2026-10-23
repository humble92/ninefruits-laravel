<?php

namespace App\Policies\Evaluation;

use App\Domain\Security\Enums\Permission;
use App\Policies\Reference\ReferenceDataPolicy;

/**
 * Evaluation forms, questions and term evaluations.
 */
class EvaluationSetupPolicy extends ReferenceDataPolicy
{
    protected function managePermission(): Permission
    {
        return Permission::EvaluationsManage;
    }
}
