<?php

namespace App\Filament\Admin\Resources\Reference\LookupTypes\Pages;

use App\Filament\Admin\Imports\ReferenceImporters;
use App\Filament\Admin\Resources\Reference\LookupTypes\LookupTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLookupTypes extends ListRecords
{
    protected static string $resource = LookupTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make(),
            ReferenceImporters::action('lookup_values')];
    }
}
