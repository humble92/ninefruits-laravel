<?php

namespace App\Policies\Reference;

use App\Domain\Security\Enums\Permission;

class StatusChangeTypePolicy extends ReferenceDataPolicy
{
    protected function managePermission(): Permission
    {
        return Permission::StudentRecordsManage;
    }
}
