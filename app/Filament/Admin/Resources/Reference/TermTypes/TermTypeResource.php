<?php

namespace App\Filament\Admin\Resources\Reference\TermTypes;

use App\Filament\Admin\Resources\Reference\ReferenceResource;
use App\Filament\Admin\Resources\Reference\TermTypes\Pages\ManageTermTypes;
use App\Filament\Admin\Support\SafeDeleteAction;
use App\Filament\Admin\Support\TranslatableFields;
use App\Models\Term\TermType;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TermTypeResource extends ReferenceResource
{
    protected static ?string $model = TermType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSun;

    protected static ?int $navigationSort = 41;

    protected static function labelKey(): string
    {
        return 'term_types';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')->label(__('reference.fields.code'))->required()->maxLength(64)->unique(ignoreRecord: true),
            TranslatableFields::make('name', __('reference.fields.name')),
            Toggle::make('is_regular')->label(__('reference.fields.is_regular'))->helperText(__('reference.help.is_regular'))->default(true),
            TextInput::make('sort_order')->label(__('reference.fields.sort_order'))->integer()->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label(__('reference.fields.code'))->searchable()->sortable(),
                TextColumn::make('name')->label(__('reference.fields.name'))->searchable(query: fn ($query, string $search) => $query->whereRaw('util.i18n_search_text(name) ILIKE ?', ['%'.$search.'%'])),
                IconColumn::make('is_regular')->label(__('reference.fields.is_regular'))->boolean(),
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
            'index' => ManageTermTypes::route('/'),
        ];
    }
}
