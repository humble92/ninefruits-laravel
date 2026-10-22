<?php

namespace App\Policies\Registration;

use App\Domain\Security\Enums\Permission;
use App\Policies\Reference\ReferenceDataPolicy;

/**
 * Rule sets and credit limit policies.
 */
class RegistrationRulePolicy extends ReferenceDataPolicy
{
    protected function managePermission(): Permission
    {
        return Permission::RegistrationRulesManage;
    }
}
