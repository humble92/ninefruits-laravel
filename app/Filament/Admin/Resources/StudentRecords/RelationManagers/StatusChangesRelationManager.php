<?php

namespace App\Filament\Admin\Resources\StudentRecords\RelationManagers;

use App\Filament\Admin\Resources\StatusChanges\StatusChangeResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Status history of the record (requests and decisions). Read only; decisions are made in
 * the status change queue, staff changes with the "Record status change" action.
 */
class StatusChangesRelationManager extends RelationManager
{
    protected static string $relationship = 'statusChanges';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('people.resources.status_changes.plural_label');
    }

    public function table(Table $table): Table
    {
        return StatusChangeResource::historyColumns($table->modifyQueryUsing(fn (Builder $query) => $query->with(['type', 'requestedBy', 'decidedBy'])));
    }
}
