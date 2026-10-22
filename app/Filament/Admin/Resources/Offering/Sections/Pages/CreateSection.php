<?php

namespace App\Filament\Admin\Resources\Offering\Sections\Pages;

use App\Domain\Offering\Actions\CreateSection as CreateSectionAction;
use App\Filament\Admin\Resources\Curriculum\Courses\CourseResource;
use App\Filament\Admin\Resources\Offering\Sections\SectionResource;
use App\Filament\Admin\Resources\Reference\OrgUnits\OrgUnitResource;
use App\Models\Curriculum\Course;
use App\Models\Term\TermSession;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateSection extends CreateRecord
{
    protected static string $resource = SectionResource::class;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(3)->schema([
                Select::make('term_session_id')->label(__('offering.fields.session'))->options(fn () => SectionResource::sessionOptions())->required()->searchable(),
                Select::make('course_id')->label(__('curriculum.resources.courses.label'))->required()->searchable()->live()
                    ->getSearchResultsUsing(fn (string $search) => CourseResource::courseSearch($search))
                    ->getOptionLabelUsing(fn ($value) => Course::query()->find($value)?->code)
                    ->afterStateUpdated(fn (Set $set, $state) => $set('org_unit_id', Course::query()->find($state)?->org_unit_id)),
                TextInput::make('section_code')->label(__('offering.fields.section_code'))->required()->maxLength(16)->alphaDash()->default('01'),
                TextInput::make('capacity')->label(__('offering.fields.capacity'))->integer()->minValue(0)->maxValue(2000)->required()->default(40),
                TextInput::make('waitlist_capacity')->label(__('registration.fields.waitlist_capacity'))->integer()->minValue(0)->maxValue(500)->default(0)
                    ->helperText(__('registration.help.waitlist_capacity')),
                Select::make('org_unit_id')->label(__('offering.fields.offering_unit'))->options(fn () => OrgUnitResource::unitOptions())->searchable()->required()
                    ->helperText(__('offering.help.offering_unit')),
                TextInput::make('instruction_language')->label(__('offering.fields.instruction_language'))->maxLength(16),
            ]),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        /** @var User $user */
        $user = Auth::user();

        return app(CreateSectionAction::class)->handle(
            TermSession::query()->findOrFail($data['term_session_id']),
            Course::query()->findOrFail($data['course_id']),
            $data,
            $user,
        );
    }

    protected function getRedirectUrl(): string
    {
        return SectionResource::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
