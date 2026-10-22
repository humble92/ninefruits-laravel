<?php

namespace App\Filament\Admin\Resources\Registration\CreditLimitPolicies\Pages;

use App\Filament\Admin\Resources\Registration\CreditLimitPolicies\CreditLimitPolicyResource;
use Filament\Resources\Pages\ManageRecords;

class ManageCreditLimitPolicies extends ManageRecords
{
    protected static string $resource = CreditLimitPolicyResource::class;

    protected function getHeaderActions(): array
    {
        return [CreditLimitPolicyResource::createAction()];
    }
}
