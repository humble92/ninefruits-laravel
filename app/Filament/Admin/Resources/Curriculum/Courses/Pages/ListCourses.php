<?php

namespace App\Filament\Admin\Resources\Curriculum\Courses\Pages;

use App\Filament\Admin\Imports\ReferenceImporters;
use App\Filament\Admin\Resources\Curriculum\Courses\CourseResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCourses extends ListRecords
{
    protected static string $resource = CourseResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make(), ReferenceImporters::action('courses')];
    }
}
