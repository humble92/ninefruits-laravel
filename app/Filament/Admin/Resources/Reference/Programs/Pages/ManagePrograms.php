<?php

namespace App\Filament\Admin\Resources\Reference\Programs\Pages;

use App\Filament\Admin\Imports\ReferenceImporters;
use App\Filament\Admin\Resources\Reference\Programs\ProgramResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePrograms extends ManageRecords
{
    protected static string $resource = ProgramResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ReferenceImporters::action('programs'),
        ];
    }
}
