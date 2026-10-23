<?php

namespace App\Filament\Admin\Resources\Reference\GradeScales\Pages;

use App\Domain\Grading\Actions\SaveGradeScale;
use App\Filament\Admin\Resources\Reference\GradeScales\GradeScaleResource;
use App\Filament\Admin\Support\FillsTranslatableFields;
use App\Filament\Admin\Support\SafeDeleteAction;
use App\Models\Grading\GradeScale;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditGradeScale extends EditRecord
{
    use FillsTranslatableFields;

    protected static string $resource = GradeScaleResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var GradeScale $record */
        return app(SaveGradeScale::class)->handle($record, $data);
    }

    protected function getHeaderActions(): array
    {
        return [SafeDeleteAction::make()->visible(fn (GradeScale $record) => ! $record->is_default)];
    }
}
