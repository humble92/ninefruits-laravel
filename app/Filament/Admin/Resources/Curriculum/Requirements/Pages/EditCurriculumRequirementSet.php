<?php

namespace App\Filament\Admin\Resources\Curriculum\Requirements\Pages;

use App\Filament\Admin\Resources\Curriculum\Requirements\CurriculumRequirementSetResource;
use App\Filament\Admin\Support\FillsTranslatableFields;
use Filament\Resources\Pages\EditRecord;

class EditCurriculumRequirementSet extends EditRecord
{
    use FillsTranslatableFields;

    protected static string $resource = CurriculumRequirementSetResource::class;
}
