<?php

namespace App\Filament\Admin\Imports;

use App\Domain\Registration\Enums\HoldType;
use App\Models\Person\StudentRecord;
use App\Models\Registration\RegistrationHold;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Validation\Rule;

/**
 * Columns: student_number, hold_type, reason, starts_at, ends_at, blocks_registration,
 * blocks_grades, blocks_certificates. Every row places a new hold (source "import"),
 * e.g. holds from the legacy SRDENY table or from the finance office.
 */
class RegistrationHoldImporter extends ReferenceImporter
{
    protected static ?string $model = RegistrationHold::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('student_number')->requiredMapping()->rules(['required', 'max:32'])->fillRecordUsing(fn () => null),
            ImportColumn::make('hold_type')->requiredMapping()->rules(['required', Rule::enum(HoldType::class)])
                ->fillRecordUsing(fn (RegistrationHold $record, string $state) => $record->hold_type = HoldType::from($state)),
            ImportColumn::make('reason')->requiredMapping()->rules(['required', 'max:1000']),
            ImportColumn::make('starts_at')->rules(['nullable', 'date']),
            ImportColumn::make('ends_at')->rules(['nullable', 'date', 'after:starts_at']),
            ImportColumn::make('blocks_registration')->boolean()->rules(['nullable', 'boolean']),
            ImportColumn::make('blocks_grades')->boolean()->rules(['nullable', 'boolean']),
            ImportColumn::make('blocks_certificates')->boolean()->rules(['nullable', 'boolean']),
        ];
    }

    public function resolveRecord(): RegistrationHold
    {
        $record = StudentRecord::query()->where('student_number', $this->data['student_number'])->first()
            ?? throw new RowImportFailedException(__('registration.import.unknown_student', ['number' => $this->data['student_number']]));

        return new RegistrationHold([
            'student_record_id' => $record->id,
            'starts_at' => now(),
            'source' => 'import',
            'created_by_id' => $this->import->user_id,
        ]);
    }

    protected function beforeSaveInRowTransaction(): void
    {
        /** @var RegistrationHold $hold */
        $hold = $this->record;
        $hold->starts_at ??= now()->toImmutable();
        $hold->blocks_registration ??= true;
        $hold->blocks_grades ??= false;
        $hold->blocks_certificates ??= false;
    }

    public static function getCompletedNotificationTitle(Import $import): string
    {
        return __('reference.import.title', ['type' => __('registration.resources.holds.plural_label')]);
    }
}
