<?php

namespace App\Filament\Admin\Resources\Curriculum\Requirements;

use App\Domain\Curriculum\Enums\ProgramRole;
use App\Domain\Curriculum\Enums\RequirementKind;
use App\Filament\Admin\NavigationGroup;
use App\Filament\Admin\Resources\Curriculum\Courses\CourseResource;
use App\Filament\Admin\Resources\Curriculum\Requirements\Pages\CreateCurriculumRequirementSet;
use App\Filament\Admin\Resources\Curriculum\Requirements\Pages\EditCurriculumRequirementSet;
use App\Filament\Admin\Resources\Curriculum\Requirements\Pages\ListCurriculumRequirementSets;
use App\Filament\Admin\Support\SafeDeleteAction;
use App\Filament\Admin\Support\TranslatableFields;
use App\Models\Curriculum\Course;
use App\Models\Curriculum\CourseCategory;
use App\Models\Curriculum\CurriculumRequirement;
use App\Models\Curriculum\CurriculumRequirementSet;
use App\Models\Organization\Program;
use BackedEnum;
use Closure;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Graduation requirements per program, role (primary, second major, minor) and catalog
 * years. Evaluated by the graduation audit (Phase 8).
 */
class CurriculumRequirementSetResource extends Resource
{
    protected static ?string $model = CurriculumRequirementSet::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Curriculum;

    protected static ?int $navigationSort = 20;

    protected static ?string $slug = 'curriculum/requirements';

    public static function getModelLabel(): string
    {
        return __('curriculum.resources.requirement_sets.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('curriculum.resources.requirement_sets.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make()->columns(3)->schema([
                Select::make('program_id')->label(__('reference.fields.programs'))->required()->searchable()
                    ->options(fn () => Program::query()->orderBy('code')->get()->mapWithKeys(fn (Program $program) => [$program->id => $program->code.' '.$program->name])),
                Select::make('program_role')->label(__('curriculum.fields.program_role'))->required()
                    ->options(collect(ProgramRole::cases())->mapWithKeys(fn (ProgramRole $role) => [$role->value => $role->label()]))
                    ->default(ProgramRole::Primary->value),
                TextInput::make('total_credits')->label(__('curriculum.fields.total_credits'))->numeric()->minValue(0)->maxValue(400),
                TextInput::make('catalog_year_from')->label(__('curriculum.fields.catalog_year_from'))->integer()->minValue(1950)->maxValue(2200)->required()
                    // The exclusion constraint decides; this explains overlaps before saving.
                    ->rule(fn (Get $get, ?CurriculumRequirementSet $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get, $record): void {
                        $overlaps = CurriculumRequirementSet::query()
                            ->where('program_id', $get('program_id'))->where('program_role', $get('program_role'))
                            ->when($record !== null, fn (Builder $query) => $query->whereKeyNot($record->id))
                            ->whereRaw('catalog_years && int4range(?::int, ?::int, \'[]\')', [(int) $value, filled($get('catalog_year_to')) ? (int) $get('catalog_year_to') : null])
                            ->exists();

                        if ($overlaps) {
                            $fail(__('curriculum.errors.requirement_set_overlap'));
                        }
                    }),
                TextInput::make('catalog_year_to')->label(__('curriculum.fields.catalog_year_to'))->integer()->minValue(1950)->maxValue(2200)->gte('catalog_year_from')
                    ->helperText(__('curriculum.help.catalog_year_to')),
                TranslatableFields::make('name', __('reference.fields.name'))->columnSpanFull(),
                TranslatableFields::make('note', __('curriculum.fields.note'), required: false)->columnSpanFull(),
            ]),
            Section::make(__('curriculum.sections.requirements'))->schema([
                Repeater::make('requirements')->hiddenLabel()->relationship()->orderColumn('sort_order')->columns(4)->defaultItems(0)
                    ->mutateRelationshipDataBeforeFillUsing(fn (array $data) => [...$data, 'name' => CurriculumRequirement::query()->find($data['id'])?->getTranslations('name') ?? []])
                    ->addActionLabel(__('curriculum.actions.add_requirement'))
                    ->itemLabel(fn (array $state) => RequirementKind::tryFrom((string) ($state['kind'] ?? ''))?->label())
                    ->schema([
                        Select::make('kind')->label(__('curriculum.fields.requirement_kind'))->required()->live()
                            ->options(collect(RequirementKind::cases())->mapWithKeys(fn (RequirementKind $kind) => [$kind->value => $kind->label()])),
                        Select::make('course_category_id')->label(__('curriculum.fields.category'))
                            ->options(fn () => CourseCategory::query()->orderBy('sort_order')->get()->pluck('name', 'id'))
                            ->visible(fn (Get $get) => $get('kind') === RequirementKind::CategoryCredits->value)
                            ->required(fn (Get $get) => $get('kind') === RequirementKind::CategoryCredits->value),
                        TextInput::make('min_credits')->label(__('curriculum.fields.min_credits'))->numeric()->minValue(0)
                            ->visible(fn (Get $get) => $get('kind') !== RequirementKind::RequiredCourses->value)
                            ->required(fn (Get $get) => $get('kind') === RequirementKind::CategoryCredits->value),
                        TextInput::make('min_courses')->label(__('curriculum.fields.min_courses'))->integer()->minValue(1)
                            ->visible(fn (Get $get) => $get('kind') === RequirementKind::CourseGroup->value),
                        Select::make('courses')->label(__('curriculum.resources.courses.plural_label'))->multiple()->relationship('courses', 'code')
                            ->searchable()->getSearchResultsUsing(fn (string $search) => CourseResource::courseSearch($search))
                            ->getOptionLabelFromRecordUsing(fn (Course $record) => $record->code)
                            ->visible(fn (Get $get) => $get('kind') !== RequirementKind::CategoryCredits->value)
                            ->columnSpan(2),
                        TranslatableFields::make('name', __('reference.fields.name'), required: false)->columnSpanFull(),
                    ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('program')->withCount('requirements'))
            ->columns([
                TextColumn::make('program.code')->label(__('reference.fields.programs'))->searchable()->sortable(),
                TextColumn::make('program_role')->label(__('curriculum.fields.program_role'))->formatStateUsing(fn (ProgramRole $state) => $state->label())->badge(),
                TextColumn::make('catalog_year_from')->label(__('curriculum.fields.catalog_year_from'))->sortable(),
                TextColumn::make('catalog_year_to')->label(__('curriculum.fields.catalog_year_to'))->placeholder('-'),
                TextColumn::make('name')->label(__('reference.fields.name')),
                TextColumn::make('total_credits')->label(__('curriculum.fields.total_credits'))->placeholder('-'),
                TextColumn::make('requirements_count')->label(__('curriculum.sections.requirements')),
            ])
            ->defaultSort('program.code')
            ->filters([
                SelectFilter::make('program_role')->label(__('curriculum.fields.program_role'))
                    ->options(collect(ProgramRole::cases())->mapWithKeys(fn (ProgramRole $role) => [$role->value => $role->label()])),
            ])
            ->recordActions([EditAction::make(), SafeDeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCurriculumRequirementSets::route('/'),
            'create' => CreateCurriculumRequirementSet::route('/create'),
            'edit' => EditCurriculumRequirementSet::route('/{record}/edit'),
        ];
    }
}
