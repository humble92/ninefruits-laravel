<?php

namespace App\Filament\Admin\Resources\Curriculum\Requirements\Pages;

use App\Filament\Admin\Resources\Curriculum\Requirements\CurriculumRequirementSetResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCurriculumRequirementSet extends CreateRecord
{
    protected static string $resource = CurriculumRequirementSetResource::class;
}
