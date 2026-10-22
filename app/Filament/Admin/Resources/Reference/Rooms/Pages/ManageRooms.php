<?php

namespace App\Filament\Admin\Resources\Reference\Rooms\Pages;

use App\Filament\Admin\Imports\ReferenceImporters;
use App\Filament\Admin\Resources\Reference\Rooms\RoomResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageRooms extends ManageRecords
{
    protected static string $resource = RoomResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ReferenceImporters::action('rooms'),
        ];
    }
}
