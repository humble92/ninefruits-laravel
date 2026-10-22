<?php

namespace App\Filament\Admin\Resources\StudentRecords\RelationManagers;

use App\Domain\Person\Enums\AdvisorRole;
use App\Models\Person\AdvisorAssignment;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AdvisorAssignmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'advisorAssignments';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('people.resources.advisor_assignments.plural_label');
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('instructor.person'))
            ->columns([
                TextColumn::make('instructor')->label(__('people.fields.advisor'))
                    ->state(fn (AdvisorAssignment $record) => $record->instructor->person->displayName()),
                TextColumn::make('role')->label(__('people.fields.advisor_role'))->formatStateUsing(fn (AdvisorRole $state) => $state->label()),
                TextColumn::make('start_date')->label(__('reference.fields.start_date'))->date(),
                TextColumn::make('end_date')->label(__('reference.fields.end_date'))->date()->placeholder('-'),
            ]);
    }
}
