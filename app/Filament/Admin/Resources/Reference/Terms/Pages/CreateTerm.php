<?php

namespace App\Filament\Admin\Resources\Reference\Terms\Pages;

use App\Filament\Admin\Resources\Reference\Terms\TermResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTerm extends CreateRecord
{
    protected static string $resource = TermResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
