<?php

namespace App\Filament\Admin\Resources\Grading\Sections\Pages;

use App\Filament\Admin\Resources\Grading\Sections\SectionGradeResource;
use Filament\Resources\Pages\ListRecords;

class ListSectionGrades extends ListRecords
{
    protected static string $resource = SectionGradeResource::class;
}
