<?php

namespace App\Filament\Admin\Resources\Reference\OrgUnits\RelationManagers;

use App\Domain\Organization\Enums\OrgUnitChangeType;
use App\Models\Organization\OrgUnitSuccession;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Units that took over from this one. Read only; recorded by the Reorganize action.
 */
class SuccessionsRelationManager extends RelationManager
{
    protected static string $relationship = 'successions';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('reference.resources.org_unit_successions.plural_label');
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('successor'))
            ->columns([
                TextColumn::make('successor.code')->label(__('reference.fields.successors')),
                TextColumn::make('successor.name')->label(__('reference.fields.name')),
                TextColumn::make('change_type')->label(__('reference.fields.change_type'))
                    ->formatStateUsing(fn (OrgUnitChangeType $state) => $state->label())->badge(),
                TextColumn::make('effective_date')->label(__('reference.fields.reorganization_date'))->date(),
                TextColumn::make('reason')->label(__('common.reason'))->limit(50)
                    ->tooltip(fn (OrgUnitSuccession $record) => $record->reason),
            ]);
    }
}
