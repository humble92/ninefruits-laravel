<?php

namespace App\Filament\Admin\Resources\Reference\OrgUnits\Pages;

use App\Filament\Admin\Imports\ReferenceImporters;
use App\Filament\Admin\Resources\Reference\OrgUnits\OrgUnitResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOrgUnits extends ListRecords
{
    protected static string $resource = OrgUnitResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make(),
            ReferenceImporters::action('org_units')];
    }
}
