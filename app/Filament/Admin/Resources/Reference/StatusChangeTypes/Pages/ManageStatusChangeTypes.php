<?php

namespace App\Filament\Admin\Resources\Reference\StatusChangeTypes\Pages;

use App\Filament\Admin\Resources\Reference\StatusChangeTypes\StatusChangeTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageStatusChangeTypes extends ManageRecords
{
    protected static string $resource = StatusChangeTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
