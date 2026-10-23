<?php

namespace App\Filament\Admin\Resources\Assessment\Types\Pages;

use App\Filament\Admin\Resources\Assessment\Types\AssessmentTypeResource;
use App\Filament\Admin\Support\FillsTranslatableFields;
use Filament\Resources\Pages\EditRecord;

class EditAssessmentType extends EditRecord
{
    use FillsTranslatableFields;

    protected static string $resource = AssessmentTypeResource::class;
}
