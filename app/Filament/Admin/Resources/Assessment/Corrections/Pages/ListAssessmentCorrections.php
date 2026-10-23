<?php

namespace App\Filament\Admin\Resources\Assessment\Corrections\Pages;

use App\Filament\Admin\Resources\Assessment\Corrections\AssessmentCorrectionResource;
use Filament\Resources\Pages\ListRecords;

class ListAssessmentCorrections extends ListRecords
{
    protected static string $resource = AssessmentCorrectionResource::class;
}
