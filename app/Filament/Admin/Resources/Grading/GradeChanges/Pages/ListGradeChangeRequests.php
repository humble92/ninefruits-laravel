<?php

namespace App\Filament\Admin\Resources\Grading\GradeChanges\Pages;

use App\Filament\Admin\Resources\Grading\GradeChanges\GradeChangeRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListGradeChangeRequests extends ListRecords
{
    protected static string $resource = GradeChangeRequestResource::class;
}
