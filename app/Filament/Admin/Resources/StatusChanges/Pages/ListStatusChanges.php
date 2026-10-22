<?php

namespace App\Filament\Admin\Resources\StatusChanges\Pages;

use App\Filament\Admin\Resources\StatusChanges\StatusChangeResource;
use Filament\Resources\Pages\ListRecords;

class ListStatusChanges extends ListRecords
{
    protected static string $resource = StatusChangeResource::class;
}
