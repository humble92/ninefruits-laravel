<?php

namespace App\Filament\Admin\Resources\Reference\TimeSlots\Pages;

use App\Filament\Admin\Resources\Reference\TimeSlots\TimeSlotResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageTimeSlots extends ManageRecords
{
    protected static string $resource = TimeSlotResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
