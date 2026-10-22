<?php

namespace App\Filament\Admin\Resources\StudentRecords;

use App\Domain\Person\Enums\StudentStatus;
use App\Filament\Admin\NavigationGroup;
use App\Filament\Admin\Resources\People\PersonResource;
use App\Filament\Admin\Resources\StudentRecords\Pages\EditStudentRecord;
use App\Filament\Admin\Resources\StudentRecords\Pages\ListStudentRecords;
use App\Filament\Admin\Resources\StudentRecords\Pages\RegisterStudentRecord;
use App\Filament\Admin\Resources\StudentRecords\Pages\ViewStudentRecord;
use App\Filament\Admin\Resources\StudentRecords\RelationManagers\AdvisorAssignmentsRelationManager;
use App\Filament\Admin\Resources\StudentRecords\RelationManagers\StatusChangesRelationManager;
use App\Models\Institution\LookupValue;
use App\Models\Organization\Program;
use App\Models\Person\Person;
use App\Models\Person\StudentRecord;
use App\Models\Term\Term;
use App\Models\User;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

/**
 * Student records (one per admission). Status changes only through status changes,
 * never by editing (docs/porting-plan.md section 8).
 */
class StudentRecordResource extends Resource
{
    protected static ?string $model = StudentRecord::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::StudentRecords;

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'student_number';

    public static function getModelLabel(): string
    {
        return __('people.resources.student_records.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('people.resources.student_records.plural_label');
    }

    public static function getEloquentQuery(): Builder
    {
        /** @var User $user */
        $user = Auth::user();

        $query = parent::getEloquentQuery();
        StudentRecord::constrainVisibleTo($query, $user);

        return $query;
    }

    /**
     * Editable details. Person data is edited on the person; the status through status changes.
     */
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('people.sections.record'))->columns(2)->schema(self::recordFields()),
        ]);
    }

    /**
     * @return list<Component>
     */
    public static function recordFields(): array
    {
        return [
            TextInput::make('catalog_year')->label(__('people.fields.catalog_year'))->helperText(__('people.help.catalog_year'))->integer()->minValue(1950)->maxValue(2200)->required(),
            TextInput::make('year_level')->label(__('people.fields.year_level'))->integer()->minValue(1)->maxValue(10)->required(),
            Select::make('admission_type_id')->label(__('people.fields.admission_type'))
                ->options(fn () => LookupValue::query()->ofType('admission_type')->where('is_active', true)->orderBy('sort_order')->get()->mapWithKeys(fn (LookupValue $value) => [$value->id => $value->name])),
            Select::make('admission_term_id')->label(__('people.fields.admission_term'))
                ->options(fn () => Term::query()->orderByDesc('start_date')->limit(40)->get()->mapWithKeys(fn (Term $term) => [$term->id => $term->name])),
            TextInput::make('institutional_email')->label(__('people.fields.institutional_email'))->helperText(__('people.help.institutional_email'))->email()->maxLength(255),
            TagsInput::make('tags')->label(__('people.fields.tags'))->helperText(__('people.help.tags')),
        ];
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('people.sections.record'))->columns(4)->schema([
                TextEntry::make('student_number')->label(__('people.fields.student_number')),
                TextEntry::make('person_name')->label(__('people.fields.name'))->state(fn (StudentRecord $record) => $record->person->displayName())
                    ->url(fn (StudentRecord $record) => PersonResource::getUrl('view', ['record' => $record->person])),
                TextEntry::make('status')->label(__('people.fields.status'))->formatStateUsing(fn (StudentStatus $state) => $state->label())->badge(),
                TextEntry::make('year_level')->label(__('people.fields.year_level')),
                TextEntry::make('program.name')->label(__('reference.fields.programs')),
                TextEntry::make('orgUnit.name')->label(__('reference.fields.org_unit')),
                TextEntry::make('admission_date')->label(__('people.fields.admission_date'))->date(),
                TextEntry::make('catalog_year')->label(__('people.fields.catalog_year')),
                TextEntry::make('admissionType.name')->label(__('people.fields.admission_type'))->placeholder('-'),
                TextEntry::make('institutional_email')->label(__('people.fields.institutional_email'))->placeholder('-'),
                TextEntry::make('graduation_date')->label(__('people.fields.graduation_date'))->date()->placeholder('-'),
                TextEntry::make('tags')->label(__('people.fields.tags'))->badge()->placeholder('-'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['person', 'program']))
            ->columns([
                TextColumn::make('student_number')->label(__('people.fields.student_number'))->searchable()->sortable(),
                TextColumn::make('person_name')->label(__('people.fields.name'))
                    ->state(fn (StudentRecord $record) => $record->person->displayName())
                    ->searchable(query: fn (Builder $query, string $search) => $query->whereHas('person', fn (Builder $person) => Person::constrainToSearch($person, $search))),
                TextColumn::make('program.name')->label(__('reference.fields.programs')),
                TextColumn::make('year_level')->label(__('people.fields.year_level'))->sortable(),
                TextColumn::make('status')->label(__('people.fields.status'))->formatStateUsing(fn (StudentStatus $state) => $state->label())->badge(),
                TextColumn::make('admission_date')->label(__('people.fields.admission_date'))->date()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label(__('people.fields.status'))
                    ->options(collect(StudentStatus::cases())->mapWithKeys(fn (StudentStatus $status) => [$status->value => $status->label()])),
                SelectFilter::make('program_id')->label(__('reference.fields.programs'))
                    ->options(fn () => Program::query()->orderBy('code')->get()->mapWithKeys(fn (Program $program) => [$program->id => $program->code.' '.$program->name])),
                SelectFilter::make('year_level')->label(__('people.fields.year_level'))
                    ->options(collect(range(1, 6))->mapWithKeys(fn (int $level) => [$level => (string) $level])->all()),
            ])
            ->defaultSort('student_number', 'desc')
            ->recordActions([ViewAction::make()]);
    }

    public static function getRelations(): array
    {
        return [StatusChangesRelationManager::class, AdvisorAssignmentsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStudentRecords::route('/'),
            'create' => RegisterStudentRecord::route('/register'),
            'view' => ViewStudentRecord::route('/{record}'),
            'edit' => EditStudentRecord::route('/{record}/edit'),
        ];
    }
}
