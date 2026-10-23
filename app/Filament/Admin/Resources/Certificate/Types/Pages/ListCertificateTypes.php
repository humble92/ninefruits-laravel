<?php

namespace App\Filament\Admin\Resources\Certificate\Types\Pages;

use App\Filament\Admin\Resources\Certificate\Types\CertificateTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCertificateTypes extends ListRecords
{
    protected static string $resource = CertificateTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
