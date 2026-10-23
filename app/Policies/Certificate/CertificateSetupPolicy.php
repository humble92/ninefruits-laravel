<?php

namespace App\Policies\Certificate;

use App\Domain\Security\Enums\Permission;
use App\Policies\Reference\ReferenceDataPolicy;

/**
 * Certificate kinds and templates.
 */
class CertificateSetupPolicy extends ReferenceDataPolicy
{
    protected function managePermission(): Permission
    {
        return Permission::CertificatesManage;
    }
}
