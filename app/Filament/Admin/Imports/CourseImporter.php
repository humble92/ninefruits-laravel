<?php

namespace App\Filament\Admin\Imports;

use App\Domain\Curriculum\Enums\GradingBasis;
use App\Models\Curriculum\Course;
use App\Models\Curriculum\CourseCategory;
use App\Models\Curriculum\CourseVersion;
use App\Models\Organization\OrgUnit;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Validation\Rule;

/**
 * One row per course version. The course is found (or created) by code; the version by
 * code and effective_start_date, so importing a revision adds a version and importing the
 * same file again updates. Overlapping periods fail the row (exclusion constraint).
 *
 * Columns: code, org_unit (code, for new courses), effective_start_date,
 * effective_end_date, title_{locale}, credits, lecture_hours, lab_hours, category (code),
 * grading_basis, counts_toward_credit_limit.
 */
class CourseImporter extends ReferenceImporter
{
    protected static ?string $model = CourseVersion::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('code')->requiredMapping()->rules(['required', 'max:32'])
                ->fillRecordUsing(fn () => null),
            ImportColumn::make('org_unit')->rules(['nullable'])
                ->fillRecordUsing(fn () => null),
            ImportColumn::make('effective_start_date')->requiredMapping()->rules(['required', 'date']),
            ImportColumn::make('effective_end_date')->rules(['nullable', 'date', 'after_or_equal:effective_start_date']),
            ...self::translatableColumns('title'),
            ImportColumn::make('credits')->requiredMappingForNewRecordsOnly()->numeric()
                ->rules(fn (?CourseVersion $record) => [$record?->exists ? 'nullable' : 'required', 'numeric', 'between:0,30']),
            ImportColumn::make('lecture_hours')->numeric()->rules(['nullable', 'numeric', 'between:0,40']),
            ImportColumn::make('lab_hours')->numeric()->rules(['nullable', 'numeric', 'between:0,40']),
            ImportColumn::make('category')->rules(['nullable', 'exists:course_categories,code'])
                ->fillRecordUsing(fn (CourseVersion $record, ?string $state) => filled($state) ? $record->course_category_id = CourseCategory::query()->where('code', $state)->value('id') : null),
            ImportColumn::make('grading_basis')->rules(['nullable', Rule::enum(GradingBasis::class)])
                ->fillRecordUsing(fn (CourseVersion $record, ?string $state) => filled($state) ? $record->grading_basis = GradingBasis::from($state) : null),
            ImportColumn::make('counts_toward_credit_limit')->boolean()->rules(['nullable', 'boolean']),
        ];
    }

    public function resolveRecord(): CourseVersion
    {
        $course = Course::query()->where('code', $this->data['code'])->first();

        if ($course === null) {
            // New course: created in beforeSaveInRowTransaction(), inside the row's savepoint.
            return new CourseVersion(['effective_start_date' => $this->data['effective_start_date']]);
        }

        return CourseVersion::query()->firstOrNew([
            'course_id' => $course->id,
            'effective_start_date' => $this->data['effective_start_date'],
        ]);
    }

    protected function beforeSaveInRowTransaction(): void
    {
        /** @var CourseVersion $version */
        $version = $this->record;

        if (array_key_exists('course_id', $version->getAttributes())) {
            return;
        }

        $unit = OrgUnit::query()->where('code', $this->data['org_unit'] ?? '')->first()
            ?? throw new RowImportFailedException(__('curriculum.import.org_unit_required'));
        $version->course_id = Course::query()->create(['code' => $this->data['code'], 'org_unit_id' => $unit->id])->id;
    }

    public static function getCompletedNotificationTitle(Import $import): string
    {
        return __('reference.import.title', ['type' => __('curriculum.resources.courses.plural_label')]);
    }
}
