<?php

namespace App\Filament\Admin\Resources\Reference\GradeScales\Pages;

use App\Domain\Grading\Actions\SaveGradeScale;
use App\Filament\Admin\Resources\Reference\GradeScales\GradeScaleResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateGradeScale extends CreateRecord
{
    protected static string $resource = GradeScaleResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(SaveGradeScale::class)->handle(null, $data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
