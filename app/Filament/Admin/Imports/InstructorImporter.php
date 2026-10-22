<?php

namespace App\Filament\Admin\Imports;

use App\Domain\Person\Enums\EmploymentType;
use App\Filament\Admin\Imports\Concerns\ImportsPersonColumns;
use App\Models\Person\Instructor;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Validation\Rule;

/**
 * Columns: employee_number, org_unit (code), employment_type, academic_title_{locale},
 * hire_date, end_date, plus the person columns. Matched by employee_number.
 */
class InstructorImporter extends ReferenceImporter
{
    use ImportsPersonColumns;

    protected static ?string $model = Instructor::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('employee_number')->requiredMapping()->rules(['required', 'max:32']),
            ImportColumn::make('org_unit')->requiredMappingForNewRecordsOnly()->relationship('orgUnit', 'code')
                ->rules(fn (?Instructor $record) => $record?->exists ? ['nullable'] : ['required']),
            ImportColumn::make('employment_type')->requiredMappingForNewRecordsOnly()
                ->rules(fn (?Instructor $record) => [$record?->exists ? 'nullable' : 'required', Rule::enum(EmploymentType::class)])
                ->fillRecordUsing(fn (Instructor $record, ?string $state) => filled($state) ? $record->employment_type = EmploymentType::from($state) : null),
            ...self::translatableColumns('academic_title', required: false),
            ImportColumn::make('hire_date')->rules(['nullable', 'date']),
            ImportColumn::make('end_date')->rules(['nullable', 'date']),
            ...self::personColumns(),
        ];
    }

    public function resolveRecord(): Instructor
    {
        $record = Instructor::query()->with('person')->firstOrNew(['employee_number' => $this->data['employee_number']]);

        if (! $record->exists) {
            $record->setRelation('person', $this->personForNewRecord());
        }

        return $record;
    }

    protected function beforeSave(): void
    {
        /** @var Instructor $record */
        $record = $this->record;
        $this->savePerson($record);
    }

    public static function getCompletedNotificationTitle(Import $import): string
    {
        return __('reference.import.title', ['type' => __('people.resources.instructors.plural_label')]);
    }
}
