<?php

namespace App\Filament\Admin\Resources\Assessment\Types\Pages;

use App\Filament\Admin\Resources\Assessment\Types\AssessmentTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAssessmentTypes extends ListRecords
{
    protected static string $resource = AssessmentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
