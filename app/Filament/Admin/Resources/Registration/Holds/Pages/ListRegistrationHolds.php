<?php

namespace App\Filament\Admin\Resources\Registration\Holds\Pages;

use App\Filament\Admin\Imports\ReferenceImporters;
use App\Filament\Admin\Resources\Registration\Holds\RegistrationHoldResource;
use Filament\Resources\Pages\ListRecords;

class ListRegistrationHolds extends ListRecords
{
    protected static string $resource = RegistrationHoldResource::class;

    protected function getHeaderActions(): array
    {
        return [RegistrationHoldResource::placeAction(), ReferenceImporters::action('registration_holds')];
    }
}
