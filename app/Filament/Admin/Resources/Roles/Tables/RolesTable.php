<?php

namespace App\Filament\Admin\Resources\Roles\Tables;

use App\Models\Security\Role;
use App\Models\Security\RoleAssignment;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RolesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount([
                'permissions',
                'assignments as active_assignments_count' => fn (Builder $query) => RoleAssignment::constrainToActive($query),
            ]))
            ->columns([
                TextColumn::make('display_name')->label(__('admin.roles.fields.display_name')),
                TextColumn::make('name')->label(__('admin.roles.fields.name'))->searchable()->sortable(),
                TextColumn::make('persona')
                    ->label(__('admin.roles.fields.persona'))
                    ->formatStateUsing(fn (Role $record) => $record->persona?->label())
                    ->badge()
                    ->placeholder('-'),
                IconColumn::make('is_system')->label(__('admin.roles.fields.is_system'))->boolean(),
                TextColumn::make('permissions_count')->label(__('admin.roles.fields.permissions_count')),
                TextColumn::make('active_assignments_count')->label(__('admin.roles.fields.active_assignments_count')),
            ])
            ->defaultSort('name')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
