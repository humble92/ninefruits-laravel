<?php

namespace App\Filament\Admin\Resources\Reference\AcademicYears\Pages;

use App\Filament\Admin\Resources\Reference\AcademicYears\AcademicYearResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageAcademicYears extends ManageRecords
{
    protected static string $resource = AcademicYearResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
