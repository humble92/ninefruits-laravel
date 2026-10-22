<?php

namespace App\Filament\Admin\Resources\People\RelationManagers;

use App\Domain\Person\Enums\StudentStatus;
use App\Filament\Admin\Resources\StudentRecords\StudentRecordResource;
use App\Models\Person\StudentRecord;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * All admissions of the person, newest first (one student number each).
 */
class StudentRecordsRelationManager extends RelationManager
{
    protected static string $relationship = 'studentRecords';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('people.resources.student_records.plural_label');
    }

    public function table(Table $table): Table
    {
        /** @var User $user */
        $user = Auth::user();

        return $table
            ->modifyQueryUsing(fn (Builder $query) => StudentRecord::constrainVisibleTo($query->with('program'), $user))
            ->columns([
                TextColumn::make('student_number')->label(__('people.fields.student_number')),
                TextColumn::make('program.name')->label(__('reference.fields.programs')),
                TextColumn::make('admission_date')->label(__('people.fields.admission_date'))->date(),
                TextColumn::make('status')->label(__('people.fields.status'))->formatStateUsing(fn (StudentStatus $state) => $state->label())->badge(),
            ])
            ->recordActions([
                Action::make('open')->label(__('people.actions.open'))
                    ->url(fn (StudentRecord $record) => StudentRecordResource::getUrl('view', ['record' => $record])),
            ]);
    }
}
