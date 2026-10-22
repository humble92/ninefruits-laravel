<?php

namespace App\Filament\Admin\Resources\Reference\OrgUnitTypes;

use App\Filament\Admin\Resources\Reference\OrgUnitTypes\Pages\ManageOrgUnitTypes;
use App\Filament\Admin\Resources\Reference\ReferenceResource;
use App\Filament\Admin\Support\SafeDeleteAction;
use App\Filament\Admin\Support\TranslatableFields;
use App\Models\Organization\OrgUnitType;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrgUnitTypeResource extends ReferenceResource
{
    protected static ?string $model = OrgUnitType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquare3Stack3d;

    protected static ?int $navigationSort = 21;

    protected static function labelKey(): string
    {
        return 'org_unit_types';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')->label(__('reference.fields.code'))->required()->maxLength(64)->unique(ignoreRecord: true),
            TranslatableFields::make('name', __('reference.fields.name')),
            Toggle::make('is_academic')->label(__('reference.fields.is_academic'))->default(true),
            TextInput::make('sort_order')->label(__('reference.fields.sort_order'))->integer()->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label(__('reference.fields.code'))->searchable()->sortable(),
                TextColumn::make('name')->label(__('reference.fields.name'))->searchable(query: fn ($query, string $search) => $query->whereRaw('util.i18n_search_text(name) ILIKE ?', ['%'.$search.'%'])),
                IconColumn::make('is_academic')->label(__('reference.fields.is_academic'))->boolean(),
                TextColumn::make('sort_order')->label(__('reference.fields.sort_order'))->sortable(),
            ])
            ->defaultSort('sort_order')
            ->recordActions([
                EditAction::make(),
                SafeDeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageOrgUnitTypes::route('/'),
        ];
    }
}
