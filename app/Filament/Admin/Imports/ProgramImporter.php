<?php

namespace App\Filament\Admin\Imports;

use App\Domain\Organization\Enums\DegreeLevel;
use App\Models\Organization\Program;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Validation\Rule;

/**
 * Columns: code, org_unit (code), degree_level, name_{locale}, degree_title_{locale},
 * standard_duration_years, effective_start_date, effective_end_date.
 */
class ProgramImporter extends ReferenceImporter
{
    protected static ?string $model = Program::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('code')->requiredMapping()->rules(['required', 'max:64']),
            ImportColumn::make('org_unit')->requiredMapping()->relationship('orgUnit', 'code')->rules(['required']),
            ImportColumn::make('degree_level')->requiredMapping()
                ->rules(['required', Rule::enum(DegreeLevel::class)]),
            ...self::translatableColumns('name'),
            ...self::translatableColumns('degree_title', required: false),
            ImportColumn::make('standard_duration_years')->numeric()->rules(['nullable', 'numeric', 'between:0.5,10']),
            ImportColumn::make('effective_start_date')->requiredMapping()->rules(['required', 'date']),
            ImportColumn::make('effective_end_date')->rules(['nullable', 'date', 'after_or_equal:effective_start_date']),
        ];
    }

    public function resolveRecord(): Program
    {
        return Program::query()->firstOrNew(['code' => $this->data['code']]);
    }

    public static function getCompletedNotificationTitle(Import $import): string
    {
        return __('reference.import.title', ['type' => __('reference.resources.programs.plural_label')]);
    }
}
