<?php

namespace App\Filament\Admin\Resources\Curriculum\Requirements\Pages;

use App\Filament\Admin\Resources\Curriculum\Requirements\CurriculumRequirementSetResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCurriculumRequirementSets extends ListRecords
{
    protected static string $resource = CurriculumRequirementSetResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
