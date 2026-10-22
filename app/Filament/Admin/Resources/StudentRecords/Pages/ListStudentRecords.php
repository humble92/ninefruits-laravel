<?php

namespace App\Filament\Admin\Resources\StudentRecords\Pages;

use App\Filament\Admin\Imports\ReferenceImporters;
use App\Filament\Admin\Resources\StudentRecords\StudentRecordResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStudentRecords extends ListRecords
{
    protected static string $resource = StudentRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label(__('people.actions.register_student')),
            ReferenceImporters::action('students'),
        ];
    }
}
