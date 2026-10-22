<?php

namespace App\Filament\Admin\Resources\Admissions\Cycles\Pages;

use App\Filament\Admin\Resources\Admissions\Cycles\AdmissionCycleResource;
use App\Filament\Admin\Support\FillsTranslatableFields;
use Filament\Resources\Pages\EditRecord;

class EditAdmissionCycle extends EditRecord
{
    use FillsTranslatableFields;

    protected static string $resource = AdmissionCycleResource::class;

    protected function getHeaderActions(): array
    {
        return [AdmissionCycleResource::releaseAction()];
    }
}
