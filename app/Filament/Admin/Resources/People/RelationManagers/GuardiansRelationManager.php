<?php

namespace App\Filament\Admin\Resources\People\RelationManagers;

use App\Domain\Person\Enums\GuardianRelationship;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class GuardiansRelationManager extends RelationManager
{
    protected static string $relationship = 'guardians';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('people.resources.guardians.plural_label');
    }

    public function isReadOnly(): bool
    {
        return ! Gate::allows('update', $this->getOwnerRecord());
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('relationship')->label(__('people.fields.relationship'))
                ->options(collect(GuardianRelationship::cases())->mapWithKeys(fn (GuardianRelationship $relationship) => [$relationship->value => $relationship->label()]))
                ->required(),
            TextInput::make('full_name')->label(__('people.fields.name'))->required()->maxLength(255),
            TextInput::make('phone')->label(__('people.fields.phone'))->tel()->maxLength(32),
            TextInput::make('email')->label(__('people.fields.contact_email'))->email()->maxLength(255),
            Textarea::make('address')->label(__('people.fields.address')),
            Toggle::make('is_primary_contact')->label(__('people.fields.is_primary_contact')),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('relationship')->label(__('people.fields.relationship'))->formatStateUsing(fn (GuardianRelationship $state) => $state->label()),
                TextColumn::make('full_name')->label(__('people.fields.name')),
                TextColumn::make('phone')->label(__('people.fields.phone'))->placeholder('-'),
                IconColumn::make('is_primary_contact')->label(__('people.fields.is_primary_contact'))->boolean(),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
