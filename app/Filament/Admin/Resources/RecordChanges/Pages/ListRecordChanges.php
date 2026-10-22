<?php

namespace App\Filament\Admin\Resources\RecordChanges\Pages;

use App\Filament\Admin\Resources\RecordChanges\RecordChangeResource;
use Filament\Resources\Pages\ListRecords;

class ListRecordChanges extends ListRecords
{
    protected static string $resource = RecordChangeResource::class;
}
