<?php

namespace App\Filament\Admin\Resources\People;

use App\Filament\Admin\NavigationGroup;
use App\Filament\Admin\Resources\People\Pages\CreatePerson;
use App\Filament\Admin\Resources\People\Pages\EditPerson;
use App\Filament\Admin\Resources\People\Pages\ListPeople;
use App\Filament\Admin\Resources\People\Pages\ViewPerson;
use App\Filament\Admin\Resources\People\RelationManagers\GuardiansRelationManager;
use App\Filament\Admin\Resources\People\RelationManagers\StudentRecordsRelationManager;
use App\Filament\Admin\Resources\People\Schemas\PersonForm;
use App\Filament\Admin\Resources\People\Schemas\PersonInfolist;
use App\Models\Person\Person;
use App\Models\User;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

/**
 * People (one row per person for life). Only people the user may see are listed
 * (Person::scopeVisibleTo, and row level security in the database).
 */
class PersonResource extends Resource
{
    protected static ?string $model = Person::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::StudentRecords;

    protected static ?int $navigationSort = 30;

    public static function getModelLabel(): string
    {
        return __('people.resources.people.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('people.resources.people.plural_label');
    }

    public static function getEloquentQuery(): Builder
    {
        /** @var User $user */
        $user = Auth::user();

        $query = parent::getEloquentQuery();
        Person::constrainVisibleTo($query, $user);

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return PersonForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PersonInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('user')->withCount('studentRecords'))
            ->columns([
                TextColumn::make('display_name')->label(__('people.fields.name'))
                    ->state(fn (Person $record) => $record->displayName())
                    ->description(fn (Person $record) => $record->romanizedName())
                    ->searchable(query: fn (Builder $query, string $search) => Person::constrainToSearch($query, $search)),
                TextColumn::make('birth_date')->label(__('people.fields.birth_date'))->date()->sortable(),
                TextColumn::make('email')->label(__('people.fields.contact_email'))->placeholder('-'),
                TextColumn::make('student_records_count')->label(__('people.fields.student_records_count')),
                IconColumn::make('has_account')->label(__('people.fields.has_account'))
                    ->state(fn (Person $record) => $record->user !== null)->boolean(),
            ])
            ->filters([
                TernaryFilter::make('merged')->label(__('people.filters.merged'))
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('merged_into_person_id'),
                        false: fn (Builder $query) => $query->whereNull('merged_into_person_id'),
                    )
                    ->default(false),
            ])
            ->defaultSort('family_name')
            ->recordActions([ViewAction::make()]);
    }

    public static function getRelations(): array
    {
        return [StudentRecordsRelationManager::class, GuardiansRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPeople::route('/'),
            'create' => CreatePerson::route('/create'),
            'view' => ViewPerson::route('/{record}'),
            'edit' => EditPerson::route('/{record}/edit'),
        ];
    }
}
