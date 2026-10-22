<?php

namespace App\Filament\Admin\Resources\Admissions\Cycles\Pages;

use App\Filament\Admin\Resources\Admissions\Cycles\AdmissionCycleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAdmissionCycles extends ListRecords
{
    protected static string $resource = AdmissionCycleResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
