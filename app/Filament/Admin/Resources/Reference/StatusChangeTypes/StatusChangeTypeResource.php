<?php

namespace App\Filament\Admin\Resources\Reference\StatusChangeTypes;

use App\Domain\Person\Enums\StudentStatus;
use App\Filament\Admin\NavigationGroup;
use App\Filament\Admin\Resources\Reference\ReferenceResource;
use App\Filament\Admin\Resources\Reference\StatusChangeTypes\Pages\ManageStatusChangeTypes;
use App\Filament\Admin\Support\SafeDeleteAction;
use App\Filament\Admin\Support\TranslatableFields;
use App\Models\Person\StudentStatusChangeType;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class StatusChangeTypeResource extends ReferenceResource
{
    protected static ?string $model = StudentStatusChangeType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static ?int $navigationSort = 70;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::StudentRecords;

    protected static function labelKey(): string
    {
        return 'status_change_types';
    }

    public static function getModelLabel(): string
    {
        return __('people.resources.status_change_types.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('people.resources.status_change_types.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')->label(__('reference.fields.code'))->required()->maxLength(64)->regex('/^[a-z][a-z0-9_]*$/')->unique(ignoreRecord: true)
                ->disabled(fn (?StudentStatusChangeType $record) => (bool) $record?->is_system),
            TranslatableFields::make('name', __('reference.fields.name')),
            Select::make('to_status')->label(__('people.fields.new_status'))
                ->options(collect(StudentStatus::cases())->mapWithKeys(fn (StudentStatus $status) => [$status->value => $status->label()]))
                ->required()->disabled(fn (?StudentStatusChangeType $record) => (bool) $record?->is_system),
            CheckboxList::make('from_statuses')->label(__('people.fields.from_statuses'))
                ->options(collect(StudentStatus::cases())->mapWithKeys(fn (StudentStatus $status) => [$status->value => $status->label()]))
                ->required()->disabled(fn (?StudentStatusChangeType $record) => (bool) $record?->is_system),
            Toggle::make('can_student_request')->label(__('people.fields.can_student_request')),
            TextInput::make('sort_order')->label(__('reference.fields.sort_order'))->integer()->default(0),
            Toggle::make('is_active')->label(__('reference.fields.is_active'))->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label(__('reference.fields.code'))->searchable()->sortable(),
                TextColumn::make('name')->label(__('reference.fields.name'))->searchable(query: fn ($query, string $search) => $query->whereRaw('util.i18n_search_text(name) ILIKE ?', ['%'.$search.'%'])),
                TextColumn::make('to_status')->label(__('people.fields.new_status'))->formatStateUsing(fn (StudentStatus $state) => $state->label()),
                IconColumn::make('can_student_request')->label(__('people.fields.can_student_request'))->boolean(),
                IconColumn::make('is_system')->label(__('reference.fields.is_system'))->boolean(),
                IconColumn::make('is_active')->label(__('reference.fields.is_active'))->boolean(),
            ])
            ->defaultSort('sort_order')
            ->recordActions([
                EditAction::make(),
                SafeDeleteAction::make()->hidden(fn (StudentStatusChangeType $record) => $record->is_system),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageStatusChangeTypes::route('/'),
        ];
    }
}
