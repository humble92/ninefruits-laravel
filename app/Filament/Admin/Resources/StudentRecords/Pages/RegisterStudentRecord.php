<?php

namespace App\Filament\Admin\Resources\StudentRecords\Pages;

use App\Domain\Person\Actions\RegisterStudent;
use App\Filament\Admin\Resources\People\Schemas\PersonForm;
use App\Filament\Admin\Resources\StudentRecords\StudentRecordResource;
use App\Models\Organization\Program;
use App\Models\Person\Person;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Registers a new admission: an existing person (e.g. a returning graduate) or a new one.
 */
class RegisterStudentRecord extends CreateRecord
{
    protected static string $resource = StudentRecordResource::class;

    public function getTitle(): string
    {
        return __('people.actions.register_student');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('people.sections.person'))->schema([
                Radio::make('person_mode')->label('')
                    ->options(['existing' => __('people.fields.existing_person'), 'new' => __('people.fields.new_person')])
                    ->default('new')->inline()->live(),
                Select::make('person_id')->label(__('people.fields.person'))
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search) => Person::query()->visibleTo($this->actor())->notMerged()->search($search)->limit(20)->get()
                        ->mapWithKeys(fn (Person $person) => [$person->id => $person->displayName().' ('.($person->birth_date?->toDateString() ?? '-').')']))
                    ->visible(fn (Get $get) => $get('person_mode') === 'existing')
                    ->required(fn (Get $get) => $get('person_mode') === 'existing'),
                Group::make(PersonForm::components('new_person.'))
                    ->visible(fn (Get $get) => $get('person_mode') === 'new'),
            ]),
            Section::make(__('people.sections.record'))->columns(2)->schema([
                Select::make('program_id')->label(__('reference.fields.programs'))
                    ->options(fn () => Program::query()->effectiveOn()->orderBy('code')->get()->mapWithKeys(fn (Program $program) => [$program->id => $program->code.' '.$program->name]))
                    ->searchable()->required(),
                DatePicker::make('admission_date')->label(__('people.fields.admission_date'))->default(now())->required(),
                TextInput::make('student_number')->label(__('people.fields.student_number'))->helperText(__('people.help.student_number_generated'))->maxLength(32),
                ...StudentRecordResource::recordFields(),
            ]),
        ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['catalog_year'] ??= (int) substr((string) $data['admission_date'], 0, 4);

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $person = $data['person_mode'] === 'existing'
            ? Person::query()->visibleTo($this->actor())->findOrFail($data['person_id'])
            : Person::query()->create(PersonForm::toAttributes((array) $data['new_person']));

        return app(RegisterStudent::class)->handle($person, Program::query()->findOrFail($data['program_id']), [
            'admission_date' => $data['admission_date'],
            'catalog_year' => (int) $data['catalog_year'],
            'year_level' => (int) $data['year_level'],
            'admission_type_id' => $data['admission_type_id'] ?? null,
            'admission_term_id' => $data['admission_term_id'] ?? null,
            'institutional_email' => $data['institutional_email'] ?? null,
            'student_number' => $data['student_number'] ?? null,
            'tags' => $data['tags'] ?? null,
        ], $this->actor());
    }

    protected function getRedirectUrl(): string
    {
        return StudentRecordResource::getUrl('view', ['record' => $this->getRecord()]);
    }

    private function actor(): User
    {
        /** @var User */
        return Auth::user();
    }
}
