<?php

namespace App\Filament\Admin\Resources\SecurityEvents\Pages;

use App\Filament\Admin\Resources\SecurityEvents\SecurityEventResource;
use Filament\Resources\Pages\ListRecords;

class ListSecurityEvents extends ListRecords
{
    protected static string $resource = SecurityEventResource::class;
}
