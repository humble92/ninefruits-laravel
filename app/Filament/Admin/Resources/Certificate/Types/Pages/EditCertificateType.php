<?php

namespace App\Filament\Admin\Resources\Certificate\Types\Pages;

use App\Filament\Admin\Resources\Certificate\Types\CertificateTypeResource;
use App\Filament\Admin\Support\FillsTranslatableFields;
use Filament\Resources\Pages\EditRecord;

class EditCertificateType extends EditRecord
{
    use FillsTranslatableFields;

    protected static string $resource = CertificateTypeResource::class;
}
