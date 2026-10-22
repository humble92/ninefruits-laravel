<?php

namespace App\Filament\Admin\Resources\Curriculum\Courses\Pages;

use App\Filament\Admin\Resources\Curriculum\Courses\CourseResource;
use Filament\Resources\Pages\EditRecord;

class EditCourse extends EditRecord
{
    protected static string $resource = CourseResource::class;
}
