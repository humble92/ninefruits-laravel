<?php

namespace App\Filament\Admin\Imports;

use App\Models\Facility\Building;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Models\Import;

/**
 * Columns: code, name_{locale}, address, is_active.
 */
class BuildingImporter extends ReferenceImporter
{
    protected static ?string $model = Building::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('code')->requiredMapping()->rules(['required', 'max:32']),
            ...self::translatableColumns('name'),
            ImportColumn::make('address')->rules(['nullable', 'max:1000']),
            ImportColumn::make('is_active')->boolean()->rules(['nullable', 'boolean']),
        ];
    }

    public function resolveRecord(): Building
    {
        return Building::query()->firstOrNew(['code' => $this->data['code']]);
    }

    public static function getCompletedNotificationTitle(Import $import): string
    {
        return __('reference.import.title', ['type' => __('reference.resources.buildings.plural_label')]);
    }
}
