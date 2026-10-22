<?php

namespace App\Filament\Admin\Resources\People\Pages;

use App\Filament\Admin\Resources\People\PersonResource;
use App\Filament\Admin\Resources\People\Schemas\PersonForm;
use Filament\Resources\Pages\EditRecord;

class EditPerson extends EditRecord
{
    protected static string $resource = PersonResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return PersonForm::toAttributes($data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
