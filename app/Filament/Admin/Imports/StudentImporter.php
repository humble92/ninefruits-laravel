<?php

namespace App\Filament\Admin\Imports;

use App\Domain\Person\Enums\StudentStatus;
use App\Filament\Admin\Imports\Concerns\ImportsPersonColumns;
use App\Models\Organization\Program;
use App\Models\Person\StudentRecord;
use Carbon\CarbonImmutable;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Validation\Rule;

/**
 * Initial load and bulk update of student records, matched by student_number.
 *
 * Columns: student_number, program (code), admission_date, catalog_year, year_level,
 * status, graduation_date, institutional_email, plus the person columns. Status changes
 * made here are data corrections: no status change history is written (use the screens
 * or status change requests for real changes).
 */
class StudentImporter extends ReferenceImporter
{
    use ImportsPersonColumns;

    protected static ?string $model = StudentRecord::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('student_number')->requiredMapping()->rules(['required', 'max:32']),
            ImportColumn::make('program')->requiredMappingForNewRecordsOnly()
                ->rules(fn (?StudentRecord $record) => $record?->exists ? ['nullable'] : ['required'])
                ->fillRecordUsing(function (StudentRecord $record, ?string $state): void {
                    if (blank($state)) {
                        return;
                    }

                    $program = Program::query()->where('code', $state)->first()
                        ?? throw new RowImportFailedException(__('people.import.unknown_program', ['code' => $state]));
                    $record->program_id = $program->id;
                    $record->org_unit_id = $program->org_unit_id;
                }),
            ImportColumn::make('admission_date')->requiredMappingForNewRecordsOnly()
                ->rules(fn (?StudentRecord $record) => $record?->exists ? ['nullable', 'date'] : ['required', 'date'])
                ->fillRecordUsing(fn (StudentRecord $record, ?string $state) => filled($state) ? $record->admission_date = CarbonImmutable::parse($state) : null),
            ImportColumn::make('catalog_year')->integer()->rules(['nullable', 'integer', 'between:1900,2200']),
            ImportColumn::make('year_level')->integer()->rules(['nullable', 'integer', 'between:1,10']),
            ImportColumn::make('status')->rules(['nullable', Rule::enum(StudentStatus::class)])
                ->fillRecordUsing(fn (StudentRecord $record, ?string $state) => filled($state) ? $record->status = StudentStatus::from($state) : null),
            ImportColumn::make('graduation_date')->rules(['nullable', 'date']),
            ImportColumn::make('institutional_email')->rules(['nullable', 'email', 'max:255']),
            ...self::personColumns(),
        ];
    }

    public function resolveRecord(): StudentRecord
    {
        $record = StudentRecord::query()->with('person')->firstOrNew(['student_number' => $this->data['student_number']]);

        if (! $record->exists) {
            $record->setRelation('person', $this->personForNewRecord());
        }

        return $record;
    }

    protected function beforeSave(): void
    {
        /** @var StudentRecord $record */
        $record = $this->record;
        $this->savePerson($record);

        if (! $record->exists) {
            $record->catalog_year ??= $record->admission_date->year;
            $record->status_changed_at = CarbonImmutable::now();
        }
    }

    public static function getCompletedNotificationTitle(Import $import): string
    {
        return __('reference.import.title', ['type' => __('people.resources.student_records.plural_label')]);
    }
}
