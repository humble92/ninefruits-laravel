<?php

namespace App\Filament\Admin\Resources\Reference\Buildings\Pages;

use App\Filament\Admin\Imports\ReferenceImporters;
use App\Filament\Admin\Resources\Reference\Buildings\BuildingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageBuildings extends ManageRecords
{
    protected static string $resource = BuildingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ReferenceImporters::action('buildings'),
        ];
    }
}
