<?php

namespace App\Filament\Admin\Resources\Instructors\Pages;

use App\Filament\Admin\Resources\Instructors\InstructorResource;
use App\Filament\Admin\Support\FillsTranslatableFields;
use Filament\Resources\Pages\EditRecord;

class EditInstructor extends EditRecord
{
    use FillsTranslatableFields;

    protected static string $resource = InstructorResource::class;
}
