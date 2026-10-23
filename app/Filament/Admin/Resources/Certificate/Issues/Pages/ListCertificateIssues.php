<?php

namespace App\Filament\Admin\Resources\Certificate\Issues\Pages;

use App\Filament\Admin\Resources\Certificate\Issues\CertificateIssueResource;
use Filament\Resources\Pages\ListRecords;

class ListCertificateIssues extends ListRecords
{
    protected static string $resource = CertificateIssueResource::class;
}
