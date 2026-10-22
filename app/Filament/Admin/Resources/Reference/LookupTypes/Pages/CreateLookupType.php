<?php

namespace App\Filament\Admin\Resources\Reference\LookupTypes\Pages;

use App\Filament\Admin\Resources\Reference\LookupTypes\LookupTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLookupType extends CreateRecord
{
    protected static string $resource = LookupTypeResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
