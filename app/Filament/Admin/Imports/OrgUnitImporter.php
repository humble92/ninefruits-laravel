<?php

namespace App\Filament\Admin\Imports;

use App\Models\Organization\OrgUnit;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Models\Import;

/**
 * Columns: code, type (org unit type code), parent (parent code), name_{locale},
 * short_name_{locale}, effective_start_date, effective_end_date, sort_order.
 * Parents must appear before their children in the file.
 */
class OrgUnitImporter extends ReferenceImporter
{
    protected static ?string $model = OrgUnit::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('code')->requiredMapping()->rules(['required', 'max:64']),
            ImportColumn::make('type')->requiredMapping()
                ->relationship('type', 'code')
                ->rules(['required']),
            ImportColumn::make('parent')
                ->relationship('parent', 'code'),
            ...self::translatableColumns('name'),
            ...self::translatableColumns('short_name', required: false),
            ImportColumn::make('effective_start_date')->requiredMapping()->rules(['required', 'date']),
            ImportColumn::make('effective_end_date')->rules(['nullable', 'date', 'after_or_equal:effective_start_date']),
            ImportColumn::make('sort_order')->integer()->rules(['nullable', 'integer']),
        ];
    }

    public function resolveRecord(): OrgUnit
    {
        return OrgUnit::query()->firstOrNew(['code' => $this->data['code']]);
    }

    public static function getCompletedNotificationTitle(Import $import): string
    {
        return __('reference.import.title', ['type' => __('reference.resources.org_units.plural_label')]);
    }
}
