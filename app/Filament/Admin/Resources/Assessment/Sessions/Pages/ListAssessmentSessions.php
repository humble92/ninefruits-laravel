<?php

namespace App\Filament\Admin\Resources\Assessment\Sessions\Pages;

use App\Filament\Admin\Resources\Assessment\Sessions\AssessmentSessionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAssessmentSessions extends ListRecords
{
    protected static string $resource = AssessmentSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
