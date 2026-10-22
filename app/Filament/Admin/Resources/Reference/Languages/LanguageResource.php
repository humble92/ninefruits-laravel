<?php

namespace App\Filament\Admin\Resources\Reference\Languages;

use App\Filament\Admin\Resources\Reference\Languages\Pages\ManageLanguages;
use App\Filament\Admin\Resources\Reference\ReferenceResource;
use App\Filament\Admin\Support\SafeDeleteAction;
use App\Filament\Admin\Support\TranslatableFields;
use App\Models\Institution\Language;
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

class LanguageResource extends ReferenceResource
{
    protected static ?string $model = Language::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLanguage;

    protected static ?int $navigationSort = 91;

    protected static function labelKey(): string
    {
        return 'languages';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')->label(__('reference.fields.iso_code'))->required()->maxLength(3)->unique(ignoreRecord: true),
            TextInput::make('alpha3_code')->label(__('reference.fields.alpha3_code'))->length(3),
            TranslatableFields::make('name', __('reference.fields.name')),
            Toggle::make('is_active')->label(__('reference.fields.is_active'))->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label(__('reference.fields.code'))->searchable()->sortable(),
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
            'index' => ManageLanguages::route('/'),
        ];
    }
}
