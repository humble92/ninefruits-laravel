<?php

namespace App\Filament\Admin\Resources\Lms\SyncLogs\Pages;

use App\Filament\Admin\Resources\Lms\SyncLogs\LmsSyncLogResource;
use Filament\Resources\Pages\ListRecords;

class ListLmsSyncLogs extends ListRecords
{
    protected static string $resource = LmsSyncLogResource::class;
}
