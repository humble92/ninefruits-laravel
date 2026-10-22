<?php

namespace App\Filament\Admin\Imports;

use App\Models\Institution\LookupType;
use App\Models\Institution\LookupValue;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Models\Import;

/**
 * Columns: type (code list code, e.g. ethnicity), code, name_{locale}, sort_order, is_active.
 */
class LookupValueImporter extends ReferenceImporter
{
    protected static ?string $model = LookupValue::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('type')->requiredMapping()->rules(['required'])
                ->fillRecordUsing(fn () => null),
            ImportColumn::make('code')->requiredMapping()->rules(['required', 'max:64']),
            ...self::translatableColumns('name'),
            ImportColumn::make('sort_order')->integer()->rules(['nullable', 'integer']),
            ImportColumn::make('is_active')->boolean()->rules(['nullable', 'boolean']),
        ];
    }

    public function resolveRecord(): LookupValue
    {
        $type = LookupType::query()->where('code', $this->data['type'])->first();

        if ($type === null) {
            throw new RowImportFailedException(__('reference.import.unknown_code', ['column' => 'type', 'code' => $this->data['type']]));
        }

        return LookupValue::query()->firstOrNew(['lookup_type_id' => $type->id, 'code' => $this->data['code']]);
    }

    public static function getCompletedNotificationTitle(Import $import): string
    {
        return __('reference.import.title', ['type' => __('reference.resources.lookup_values.plural_label')]);
    }
}
