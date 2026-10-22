<?php

namespace App\Filament\Admin\Resources\Reference\Countries\Pages;

use App\Filament\Admin\Resources\Reference\Countries\CountryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCountries extends ManageRecords
{
    protected static string $resource = CountryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
