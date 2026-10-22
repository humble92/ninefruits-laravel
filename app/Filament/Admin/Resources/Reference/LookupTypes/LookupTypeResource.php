<?php

namespace App\Filament\Admin\Resources\Reference\LookupTypes;

use App\Filament\Admin\Resources\Reference\LookupTypes\Pages\CreateLookupType;
use App\Filament\Admin\Resources\Reference\LookupTypes\Pages\EditLookupType;
use App\Filament\Admin\Resources\Reference\LookupTypes\Pages\ListLookupTypes;
use App\Filament\Admin\Resources\Reference\LookupTypes\RelationManagers\ValuesRelationManager;
use App\Filament\Admin\Resources\Reference\ReferenceResource;
use App\Filament\Admin\Support\TranslatableFields;
use App\Models\Institution\LookupType;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Code data lists (legacy FNATION, FADMDIST, ROOM.FORWHAT ...). System lists are used by
 * the application; their codes cannot change and they cannot be deleted.
 */
class LookupTypeResource extends ReferenceResource
{
    protected static ?string $model = LookupType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedListBullet;

    protected static ?int $navigationSort = 80;

    protected static function labelKey(): string
    {
        return 'lookup_types';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')->label(__('reference.fields.code'))
                ->required()->maxLength(64)->regex('/^[a-z][a-z0-9_]*$/')->unique(ignoreRecord: true)
                ->disabled(fn (?LookupType $record) => (bool) $record?->is_system),
            TranslatableFields::make('name', __('reference.fields.name')),
            TranslatableFields::make('description', __('reference.fields.description'), required: false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label(__('reference.fields.code'))->searchable()->sortable(),
                TextColumn::make('name')->label(__('reference.fields.name')),
                TextColumn::make('values_count')->label(__('reference.fields.values_count'))->counts('values'),
                IconColumn::make('is_system')->label(__('reference.fields.is_system'))->boolean(),
            ])
            ->defaultSort('code')
            ->recordActions([EditAction::make()]);
    }

    public static function getRelations(): array
    {
        return [ValuesRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLookupTypes::route('/'),
            'create' => CreateLookupType::route('/create'),
            'edit' => EditLookupType::route('/{record}/edit'),
        ];
    }
}
