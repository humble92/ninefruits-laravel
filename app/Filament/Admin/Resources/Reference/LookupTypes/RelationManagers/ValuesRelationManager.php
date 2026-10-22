<?php

namespace App\Filament\Admin\Resources\Reference\LookupTypes\RelationManagers;

use App\Filament\Admin\Support\SafeDeleteAction;
use App\Filament\Admin\Support\TranslatableFields;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ValuesRelationManager extends RelationManager
{
    protected static string $relationship = 'values';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('reference.resources.lookup_values.plural_label');
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')->label(__('reference.fields.code'))->required()->maxLength(64)
                ->unique(ignoreRecord: true, modifyRuleUsing: fn ($rule) => $rule->where('lookup_type_id', $this->getOwnerRecord()->getKey())),
            TranslatableFields::make('name', __('reference.fields.name')),
            TextInput::make('sort_order')->label(__('reference.fields.sort_order'))->integer()->default(0),
            Toggle::make('is_active')->label(__('reference.fields.is_active'))->default(true),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label(__('reference.fields.code'))->searchable(),
                TextColumn::make('name')->label(__('reference.fields.name')),
                TextColumn::make('sort_order')->label(__('reference.fields.sort_order'))->sortable(),
                IconColumn::make('is_active')->label(__('reference.fields.is_active'))->boolean(),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), SafeDeleteAction::make()]);
    }
}
