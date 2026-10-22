<?php

namespace App\Filament\Admin\Resources\Registration\Overrides\Pages;

use App\Filament\Admin\Resources\Registration\Overrides\RegistrationOverrideResource;
use Filament\Resources\Pages\ListRecords;

class ListRegistrationOverrides extends ListRecords
{
    protected static string $resource = RegistrationOverrideResource::class;

    protected function getHeaderActions(): array
    {
        return [RegistrationOverrideResource::grantAction()];
    }
}
