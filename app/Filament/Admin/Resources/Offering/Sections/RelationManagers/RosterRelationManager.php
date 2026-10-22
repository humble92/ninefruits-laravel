<?php

namespace App\Filament\Admin\Resources\Offering\Sections\RelationManagers;

use App\Domain\Registration\Enums\EnrollmentSource;
use App\Domain\Registration\Enums\EnrollmentStatus;
use App\Filament\Admin\Resources\Registration\Enrollments\EnrollmentResource;
use App\Models\Offering\Section;
use App\Models\Registration\Enrollment;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * The students registered in a section, with staff registration and drop/withdraw.
 */
class RosterRelationManager extends RelationManager
{
    protected static string $relationship = 'enrollments';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        /** @var Section $ownerRecord */
        return __('registration.resources.roster.title', ['count' => $ownerRecord->enrolled_count, 'capacity' => $ownerRecord->capacity]);
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        /** @var Section $section */
        $section = $this->getOwnerRecord();

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['studentRecord.person', 'studentRecord.program', 'section']))
            ->columns([
                TextColumn::make('studentRecord.student_number')->label(__('people.fields.student_number'))->searchable(),
                TextColumn::make('name')->label(__('people.fields.name'))->state(fn (Enrollment $record) => $record->studentRecord->person->displayName()),
                TextColumn::make('studentRecord.program.name')->label(__('reference.resources.programs.label')),
                TextColumn::make('status')->label(__('registration.fields.status'))->formatStateUsing(fn (EnrollmentStatus $state) => $state->label())->badge(),
                IconColumn::make('is_retake')->label(__('registration.fields.is_retake'))->boolean(),
                TextColumn::make('source')->label(__('registration.fields.source'))->formatStateUsing(fn (EnrollmentSource $state) => $state->label()),
                TextColumn::make('enrolled_at')->label(__('registration.fields.enrolled_at'))->dateTime()->sortable(),
            ])
            ->defaultSort('enrolled_at')
            ->filters([
                SelectFilter::make('status')->label(__('registration.fields.status'))
                    ->options(collect(EnrollmentStatus::cases())->mapWithKeys(fn (EnrollmentStatus $status) => [$status->value => $status->label()]))
                    ->default(EnrollmentStatus::Enrolled->value),
            ])
            ->headerActions([EnrollmentResource::staffEnrollAction($section)->visible(fn () => $section->isRegistrationManageableBy(EnrollmentResource::actor()))])
            ->recordActions([EnrollmentResource::endAction('drop'), EnrollmentResource::endAction('withdraw')]);
    }
}
