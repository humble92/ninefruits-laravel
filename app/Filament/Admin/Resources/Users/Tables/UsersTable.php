<?php

namespace App\Filament\Admin\Resources\Users\Tables;

use App\Domain\Security\Services\MfaPolicy;
use App\Models\User;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('person'))
            ->columns([
                TextColumn::make('name')->label(__('admin.users.fields.name'))
                    ->state(fn (User $record) => $record->displayName())
                    ->description(fn (User $record) => $record->person_id !== null ? __('admin.users.fields.linked_person') : null)
                    ->searchable()->sortable(),
                TextColumn::make('email')->label(__('admin.users.fields.email'))->searchable()->sortable(),
                IconColumn::make('is_active')->label(__('admin.users.fields.is_active'))->boolean(),
                IconColumn::make('mfa_enrolled')
                    ->label(__('admin.users.fields.mfa_enrolled'))
                    ->state(fn (User $record) => app(MfaPolicy::class)->isEnrolled($record))
                    ->boolean(),
                TextColumn::make('last_login_at')->label(__('admin.users.fields.last_login_at'))->dateTime()->sortable()->placeholder('-'),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label(__('admin.users.fields.is_active')),
            ])
            ->defaultSort('name')
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}
