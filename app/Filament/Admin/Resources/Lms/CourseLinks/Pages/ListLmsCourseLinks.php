<?php

namespace App\Filament\Admin\Resources\Lms\CourseLinks\Pages;

use App\Filament\Admin\Resources\Lms\CourseLinks\LmsCourseLinkResource;
use Filament\Resources\Pages\ListRecords;

class ListLmsCourseLinks extends ListRecords
{
    protected static string $resource = LmsCourseLinkResource::class;
}
