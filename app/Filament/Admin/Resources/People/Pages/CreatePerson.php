<?php

namespace App\Filament\Admin\Resources\People\Pages;

use App\Filament\Admin\Resources\People\PersonResource;
use App\Filament\Admin\Resources\People\Schemas\PersonForm;
use Filament\Resources\Pages\CreateRecord;

class CreatePerson extends CreateRecord
{
    protected static string $resource = PersonResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return PersonForm::toAttributes($data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
