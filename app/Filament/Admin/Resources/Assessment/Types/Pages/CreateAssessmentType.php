<?php

namespace App\Filament\Admin\Resources\Assessment\Types\Pages;

use App\Filament\Admin\Resources\Assessment\Types\AssessmentTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAssessmentType extends CreateRecord
{
    protected static string $resource = AssessmentTypeResource::class;
}
