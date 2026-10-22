<?php

namespace App\Filament\Admin\Resources\Instructors\Pages;

use App\Filament\Admin\Resources\Instructors\InstructorResource;
use App\Filament\Admin\Resources\People\Schemas\PersonForm;
use App\Models\Person\Instructor;
use App\Models\Person\Person;
use App\Models\User;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreateInstructor extends CreateRecord
{
    protected static string $resource = InstructorResource::class;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('people.sections.person'))->schema([
                Radio::make('person_mode')->label('')
                    ->options(['existing' => __('people.fields.existing_person'), 'new' => __('people.fields.new_person')])
                    ->default('new')->inline()->live(),
                Select::make('person_id')->label(__('people.fields.person'))
                    ->searchable()
                    ->getSearchResultsUsing(function (string $search) {
                        /** @var User $user */
                        $user = Auth::user();

                        return Person::query()->visibleTo($user)->notMerged()->whereDoesntHave('instructor')->search($search)->limit(20)->get()
                            ->mapWithKeys(fn (Person $person) => [$person->id => $person->displayName()]);
                    })
                    ->visible(fn (Get $get) => $get('person_mode') === 'existing')
                    ->required(fn (Get $get) => $get('person_mode') === 'existing'),
                Group::make(PersonForm::components('new_person.'))->visible(fn (Get $get) => $get('person_mode') === 'new'),
            ]),
            Section::make(__('people.resources.instructors.label'))->columns(2)->schema(InstructorResource::instructorFields()),
        ]);
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $person = $data['person_mode'] === 'existing'
                ? Person::query()->findOrFail($data['person_id'])
                : Person::query()->create(PersonForm::toAttributes((array) $data['new_person']));

            return Instructor::query()->create([
                'person_id' => $person->id,
                'employee_number' => $data['employee_number'],
                'org_unit_id' => $data['org_unit_id'],
                'employment_type' => $data['employment_type'],
                'academic_title' => $data['academic_title'] ?? null,
                'hire_date' => $data['hire_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
            ]);
        });
    }
}
