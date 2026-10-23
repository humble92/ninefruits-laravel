<?php

namespace App\Filament\Admin\Resources\Certificate\Types\Pages;

use App\Filament\Admin\Resources\Certificate\Types\CertificateTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCertificateType extends CreateRecord
{
    protected static string $resource = CertificateTypeResource::class;
}
