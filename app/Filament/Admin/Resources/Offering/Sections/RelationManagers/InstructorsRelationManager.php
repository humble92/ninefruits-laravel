<?php

namespace App\Filament\Admin\Resources\Offering\Sections\RelationManagers;

use App\Domain\Offering\Actions\AssignSectionInstructor;
use App\Domain\Offering\Enums\InstructorRole;
use App\Filament\Admin\Support\ShowsActionErrors;
use App\Models\Offering\Section;
use App\Models\Offering\SectionInstructor;
use App\Models\Person\Instructor;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * Instructors of a section (several allowed, e.g. team teaching).
 */
class InstructorsRelationManager extends RelationManager
{
    protected static string $relationship = 'instructorAssignments';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('offering.fields.instructors');
    }

    /**
     * Only staff who manage the section change it (sections.manage in its unit's scope).
     */
    public function isReadOnly(): bool
    {
        return ! Gate::allows('update', $this->getOwnerRecord());
    }

    public function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Select::make('instructor_id')->label(__('people.resources.instructors.label'))->required()->searchable()
                ->options(fn () => Instructor::query()->current()->with(['person', 'orgUnit'])->get()
                    ->sortBy(fn (Instructor $instructor) => $instructor->person->displayName())
                    ->mapWithKeys(fn (Instructor $instructor) => [$instructor->id => $instructor->person->displayName().' ('.$instructor->employee_number.', '.$instructor->orgUnit->name.')'])),
            Select::make('role')->label(__('offering.fields.instructor_role'))->required()->default(InstructorRole::Primary->value)
                ->options(collect(InstructorRole::cases())->mapWithKeys(fn (InstructorRole $role) => [$role->value => $role->label()])),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('instructor.person'))
            ->columns([
                TextColumn::make('instructor.person_name')->label(__('people.fields.name'))->state(fn (SectionInstructor $record) => $record->instructor->person->displayName()),
                TextColumn::make('instructor.employee_number')->label(__('people.fields.employee_number')),
                TextColumn::make('role')->label(__('offering.fields.instructor_role'))->formatStateUsing(fn (InstructorRole $state) => $state->label()),
            ])
            ->headerActions([
                CreateAction::make()->label(__('offering.actions.assign_instructor'))
                    ->using(fn (array $data) => ShowsActionErrors::run(function () use ($data) {
                        /** @var Section $section */
                        $section = $this->getOwnerRecord();
                        /** @var User $user */
                        $user = Auth::user();

                        return app(AssignSectionInstructor::class)->handle($section, Instructor::query()->findOrFail($data['instructor_id']), InstructorRole::from($data['role']), $user);
                    })),
            ])
            ->recordActions([DeleteAction::make()]);
    }
}
