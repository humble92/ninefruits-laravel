<?php

namespace App\Filament\Admin\Resources\Reference\Countries;

use App\Filament\Admin\Resources\Reference\Countries\Pages\ManageCountries;
use App\Filament\Admin\Resources\Reference\ReferenceResource;
use App\Filament\Admin\Support\SafeDeleteAction;
use App\Filament\Admin\Support\TranslatableFields;
use App\Models\Institution\Country;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CountryResource extends ReferenceResource
{
    protected static ?string $model = Country::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static ?int $navigationSort = 90;

    protected static function labelKey(): string
    {
        return 'countries';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')->label(__('reference.fields.iso_code'))->required()->length(2)->unique(ignoreRecord: true),
            TextInput::make('alpha3_code')->label(__('reference.fields.alpha3_code'))->required()->length(3)->unique(ignoreRecord: true),
            TextInput::make('numeric_code')->label(__('reference.fields.numeric_code'))->length(3),
            TranslatableFields::make('name', __('reference.fields.name')),
            Toggle::make('is_active')->label(__('reference.fields.is_active'))->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label(__('reference.fields.code'))->searchable()->sortable(),
                TextColumn::make('alpha3_code')->label(__('reference.fields.alpha3_code')),
                TextColumn::make('name')->label(__('reference.fields.name'))->searchable(query: fn ($query, string $search) => $query->whereRaw('util.i18n_search_text(name) ILIKE ?', ['%'.$search.'%'])),
                IconColumn::make('is_active')->label(__('reference.fields.is_active'))->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label(__('reference.fields.is_active')),
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
            'index' => ManageCountries::route('/'),
        ];
    }
}
