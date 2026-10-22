<?php

namespace App\Filament\Admin\Resources\Reference\OrgUnitTypes\Pages;

use App\Filament\Admin\Resources\Reference\OrgUnitTypes\OrgUnitTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageOrgUnitTypes extends ManageRecords
{
    protected static string $resource = OrgUnitTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
