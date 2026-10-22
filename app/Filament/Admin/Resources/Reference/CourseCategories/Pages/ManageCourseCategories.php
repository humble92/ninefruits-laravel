<?php

namespace App\Filament\Admin\Resources\Reference\CourseCategories\Pages;

use App\Filament\Admin\Resources\Reference\CourseCategories\CourseCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCourseCategories extends ManageRecords
{
    protected static string $resource = CourseCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
