<?php

namespace App\Filament\Admin\Resources\Instructors;

use App\Domain\Person\Enums\EmploymentType;
use App\Filament\Admin\NavigationGroup;
use App\Filament\Admin\Resources\Instructors\Pages\CreateInstructor;
use App\Filament\Admin\Resources\Instructors\Pages\EditInstructor;
use App\Filament\Admin\Resources\Instructors\Pages\ListInstructors;
use App\Filament\Admin\Resources\Reference\OrgUnits\OrgUnitResource;
use App\Filament\Admin\Support\TranslatableFields;
use App\Models\Person\Instructor;
use App\Models\Person\Person;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Instructors (legacy FACULTY). Person details are edited on the person.
 */
class InstructorResource extends Resource
{
    protected static ?string $model = Instructor::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartLine;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::StudentRecords;

    protected static ?int $navigationSort = 40;

    public static function getModelLabel(): string
    {
        return __('people.resources.instructors.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('people.resources.instructors.plural_label');
    }

    /**
     * @return list<Component>
     */
    public static function instructorFields(): array
    {
        return [
            TextInput::make('employee_number')->label(__('people.fields.employee_number'))->required()->maxLength(32)->unique(ignoreRecord: true),
            Select::make('org_unit_id')->label(__('reference.fields.org_unit'))->options(fn () => OrgUnitResource::unitOptions())->searchable()->required(),
            Select::make('employment_type')->label(__('people.fields.employment_type'))
                ->options(collect(EmploymentType::cases())->mapWithKeys(fn (EmploymentType $type) => [$type->value => $type->label()]))->required(),
            TranslatableFields::make('academic_title', __('people.fields.academic_title'), required: false),
            DatePicker::make('hire_date')->label(__('people.fields.hire_date')),
            DatePicker::make('end_date')->label(__('people.fields.end_date'))->afterOrEqual('hire_date'),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components(self::instructorFields());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['person', 'orgUnit']))
            ->columns([
                TextColumn::make('employee_number')->label(__('people.fields.employee_number'))->searchable()->sortable(),
                TextColumn::make('name')->label(__('people.fields.name'))->state(fn (Instructor $record) => $record->person->displayName())
                    ->searchable(query: fn (Builder $query, string $search) => $query->whereHas('person', fn (Builder $person) => Person::constrainToSearch($person, $search))),
                TextColumn::make('orgUnit.name')->label(__('reference.fields.org_unit')),
                TextColumn::make('employment_type')->label(__('people.fields.employment_type'))->formatStateUsing(fn (EmploymentType $state) => $state->label()),
                TextColumn::make('academic_title')->label(__('people.fields.academic_title'))->placeholder('-'),
                TextColumn::make('end_date')->label(__('people.fields.end_date'))->date()->placeholder('-'),
            ])
            ->filters([
                Filter::make('current')->label(__('people.filters.current_instructors'))->default()
                    ->query(fn (Builder $query) => $query->where(fn (Builder $query) => $query->whereNull('end_date')->orWhereDate('end_date', '>=', now()))),
            ])
            ->defaultSort('employee_number')
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInstructors::route('/'),
            'create' => CreateInstructor::route('/create'),
            'edit' => EditInstructor::route('/{record}/edit'),
        ];
    }
}
