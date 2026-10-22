<?php

namespace App\Filament\Admin\Resources\Reference\Buildings;

use App\Filament\Admin\Resources\Reference\Buildings\Pages\ManageBuildings;
use App\Filament\Admin\Resources\Reference\ReferenceResource;
use App\Filament\Admin\Support\SafeDeleteAction;
use App\Filament\Admin\Support\TranslatableFields;
use App\Models\Facility\Building;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BuildingResource extends ReferenceResource
{
    protected static ?string $model = Building::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?int $navigationSort = 30;

    protected static function labelKey(): string
    {
        return 'buildings';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')->label(__('reference.fields.code'))->required()->maxLength(32)->unique(ignoreRecord: true),
            TranslatableFields::make('name', __('reference.fields.name')),
            Textarea::make('address')->label(__('reference.fields.address')),
            Toggle::make('is_active')->label(__('reference.fields.is_active'))->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label(__('reference.fields.code'))->searchable()->sortable(),
                TextColumn::make('name')->label(__('reference.fields.name'))->searchable(query: fn ($query, string $search) => $query->whereRaw('util.i18n_search_text(name) ILIKE ?', ['%'.$search.'%'])),
                TextColumn::make('rooms_count')->label(__('reference.fields.rooms_count'))->counts('rooms'),
                IconColumn::make('is_active')->label(__('reference.fields.is_active'))->boolean(),
            ])
            ->defaultSort('code')
            ->recordActions([
                EditAction::make(),
                SafeDeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageBuildings::route('/'),
        ];
    }
}
