<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use App\Domain\Institution\Services\SupportedLocales;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        $locales = app(SupportedLocales::class);

        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('admin.users.fields.name'))
                    ->helperText(__('admin.users.help.name'))
                    ->required(fn (?User $record) => $record?->person_id === null)
                    ->maxLength(255),
                TextInput::make('email')
                    ->label(__('admin.users.fields.email'))
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Select::make('locale')
                    ->label(__('admin.users.fields.locale'))
                    ->options(collect($locales->all())->mapWithKeys(fn (string $locale) => [$locale => $locales->label($locale)])),
                Toggle::make('is_active')
                    ->label(__('admin.users.fields.is_active'))
                    ->default(true)
                    ->hiddenOn('create'),
            ]);
    }
}
