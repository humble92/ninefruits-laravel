<?php

namespace App\Filament\Admin\Resources\Curriculum\Courses;

use App\Domain\Curriculum\Enums\GradingBasis;
use App\Domain\Curriculum\Enums\RequisiteKind;
use App\Domain\Institution\Services\SupportedLocales;
use App\Filament\Admin\NavigationGroup;
use App\Filament\Admin\Resources\Curriculum\Courses\Pages\CreateCourse;
use App\Filament\Admin\Resources\Curriculum\Courses\Pages\EditCourse;
use App\Filament\Admin\Resources\Curriculum\Courses\Pages\ListCourses;
use App\Filament\Admin\Resources\Curriculum\Courses\RelationManagers\EquivalenciesRelationManager;
use App\Filament\Admin\Resources\Curriculum\Courses\RelationManagers\VersionsRelationManager;
use App\Filament\Admin\Resources\Reference\OrgUnits\OrgUnitResource;
use App\Filament\Admin\Support\TranslatableFields;
use App\Models\Curriculum\Course;
use App\Models\Curriculum\CourseCategory;
use App\Models\Curriculum\CourseVersion;
use BackedEnum;
use Closure;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * Courses (legacy SFOBJECT) with dated versions, requisites and equivalencies.
 */
class CourseResource extends Resource
{
    protected static ?string $model = Course::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Curriculum;

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'curriculum/courses';

    protected static ?string $recordTitleAttribute = 'code';

    public static function getModelLabel(): string
    {
        return __('curriculum.resources.courses.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('curriculum.resources.courses.plural_label');
    }

    /**
     * @return list<Component>
     */
    public static function courseFields(): array
    {
        return [
            TextInput::make('code')->label(__('curriculum.fields.code'))->required()->maxLength(32)->alphaDash()->unique(ignoreRecord: true),
            Select::make('org_unit_id')->label(__('curriculum.fields.managing_unit'))->options(fn () => OrgUnitResource::unitOptions())->searchable()->required(),
            Toggle::make('is_active')->label(__('reference.fields.is_active'))->default(true),
        ];
    }

    /**
     * Version fields. $courseId gives the overlap check its course (null for a new course).
     *
     * @return list<Component>
     */
    public static function versionFields(Closure $courseId): array
    {
        $locales = app(SupportedLocales::class);

        return [
            DatePicker::make('effective_start_date')->label(__('reference.fields.effective_start_date'))->required()
                // The exclusion constraint decides; this explains overlaps before saving.
                ->rule(fn (Get $get, ?Model $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get, $record, $courseId): void {
                    $id = $courseId();
                    $end = $get('effective_end_date') ?: '9999-12-31';

                    if ($id !== null && CourseVersion::query()->where('course_id', $id)
                        ->when($record instanceof CourseVersion, fn (Builder $query) => $query->whereKeyNot($record->getKey()))
                        ->where('effective_start_date', '<=', $end)
                        ->where(fn (Builder $query) => $query->whereNull('effective_end_date')->orWhere('effective_end_date', '>=', $value))
                        ->exists()) {
                        $fail(__('curriculum.errors.version_overlap'));
                    }
                }),
            DatePicker::make('effective_end_date')->label(__('reference.fields.effective_end_date'))->afterOrEqual('effective_start_date'),
            TranslatableFields::make('title', __('curriculum.fields.title'))->columnSpanFull(),
            Fieldset::make(__('curriculum.fields.description'))->columnSpanFull()->schema(array_map(
                fn (string $locale) => Textarea::make("description.{$locale}")->label($locales->label($locale))->rows(2)->maxLength(2000),
                $locales->all(),
            )),
            TextInput::make('credits')->label(__('curriculum.fields.credits'))->numeric()->minValue(0)->maxValue(30)->step(0.5)->required(),
            TextInput::make('lecture_hours')->label(__('curriculum.fields.lecture_hours'))->numeric()->minValue(0)->maxValue(40)->step(0.5)->default(0),
            TextInput::make('lab_hours')->label(__('curriculum.fields.lab_hours'))->numeric()->minValue(0)->maxValue(40)->step(0.5)->default(0),
            Select::make('course_category_id')->label(__('curriculum.fields.category'))
                ->options(fn () => CourseCategory::query()->where('is_active', true)->orderBy('sort_order')->get()->pluck('name', 'id')),
            Select::make('grading_basis')->label(__('curriculum.fields.grading_basis'))
                ->options(collect(GradingBasis::cases())->mapWithKeys(fn (GradingBasis $basis) => [$basis->value => $basis->label()]))
                ->default(GradingBasis::Graded->value)->required(),
            Toggle::make('counts_toward_credit_limit')->label(__('curriculum.fields.counts_toward_credit_limit'))->default(true)
                ->helperText(__('curriculum.help.counts_toward_credit_limit')),
            Toggle::make('is_repeatable')->label(__('curriculum.fields.is_repeatable'))->helperText(__('curriculum.help.is_repeatable')),
            Toggle::make('requires_evaluation')->label(__('evaluation.fields.requires_evaluation'))->default(true)
                ->helperText(__('evaluation.help.requires_evaluation')),
        ];
    }

    public static function requisitesField(): Repeater
    {
        return Repeater::make('requisites')->label(__('curriculum.fields.requisites'))->relationship()->columns(5)->columnSpanFull()->defaultItems(0)
            ->helperText(__('curriculum.help.requisites'))
            ->schema([
                Select::make('kind')->label(__('curriculum.fields.requisite_kind'))
                    ->options(collect(RequisiteKind::cases())->mapWithKeys(fn (RequisiteKind $kind) => [$kind->value => $kind->label()]))
                    ->default(RequisiteKind::Prerequisite->value)->required(),
                TextInput::make('group_number')->label(__('curriculum.fields.group_number'))->integer()->minValue(1)->maxValue(20)->default(1)->required(),
                Select::make('required_course_id')->label(__('curriculum.fields.required_course'))->searchable()->required()
                    ->getSearchResultsUsing(fn (string $search) => self::courseSearch($search))
                    ->getOptionLabelUsing(fn ($value) => Course::query()->find($value)?->code),
                TextInput::make('minimum_grade')->label(__('curriculum.fields.minimum_grade'))->maxLength(8),
                Toggle::make('allows_concurrent')->label(__('curriculum.fields.allows_concurrent'))->inline(false),
            ]);
    }

    /**
     * Course options by code or current title, for searchable selects.
     *
     * @return array<int, string>
     */
    public static function courseSearch(string $search): array
    {
        return CourseVersion::query()->effectiveOn()->with('course')
            ->where(fn (Builder $query) => $query
                ->whereHas('course', fn (Builder $course) => $course->where('code', 'ilike', $search.'%'))
                ->orWhereRaw('util.i18n_search_text(title) ILIKE ?', ['%'.$search.'%']))
            ->limit(30)->get()
            ->mapWithKeys(fn (CourseVersion $version) => [$version->course_id => $version->course->code.' '.$version->title])
            ->all();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components(self::courseFields());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('orgUnit'))
            ->columns([
                TextColumn::make('code')->label(__('curriculum.fields.code'))->searchable()->sortable(),
                TextColumn::make('title')->label(__('curriculum.fields.title'))
                    ->state(fn (Course $record) => $record->versionOn()->title ?? '-')
                    ->searchable(query: fn (Builder $query, string $search) => $query->whereHas('versions', fn (Builder $version) => $version->whereRaw('util.i18n_search_text(title) ILIKE ?', ['%'.$search.'%']))),
                TextColumn::make('credits')->label(__('curriculum.fields.credits'))->state(fn (Course $record) => $record->versionOn()->credits ?? '-'),
                TextColumn::make('orgUnit.name')->label(__('curriculum.fields.managing_unit')),
                IconColumn::make('is_active')->label(__('reference.fields.is_active'))->boolean(),
            ])
            ->defaultSort('code')
            ->filters([
                SelectFilter::make('org_unit_id')->label(__('curriculum.fields.managing_unit'))->options(fn () => OrgUnitResource::unitOptions()),
                TernaryFilter::make('is_active')->label(__('reference.fields.is_active'))->default(true),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getRelations(): array
    {
        return [VersionsRelationManager::class, EquivalenciesRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCourses::route('/'),
            'create' => CreateCourse::route('/create'),
            'edit' => EditCourse::route('/{record}/edit'),
        ];
    }

    public static function getRecordTitle(?Model $record): string
    {
        return $record instanceof Course ? $record->code.' '.($record->versionOn()->title ?? '') : '';
    }
}
