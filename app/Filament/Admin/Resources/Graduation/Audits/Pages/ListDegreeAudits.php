<?php

namespace App\Filament\Admin\Resources\Graduation\Audits\Pages;

use App\Filament\Admin\Resources\Graduation\Audits\DegreeAuditResource;
use Filament\Resources\Pages\ListRecords;

class ListDegreeAudits extends ListRecords
{
    protected static string $resource = DegreeAuditResource::class;
}
