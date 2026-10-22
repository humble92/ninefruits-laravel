<?php

namespace App\Filament\Admin\Resources\Curriculum\Courses\Pages;

use App\Domain\Curriculum\Actions\CreateCourse as CreateCourseAction;
use App\Filament\Admin\Resources\Curriculum\Courses\CourseResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class CreateCourse extends CreateRecord
{
    protected static string $resource = CourseResource::class;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('curriculum.sections.course'))->columns(3)->schema(CourseResource::courseFields()),
            Section::make(__('curriculum.sections.first_version'))->columns(3)->schema(CourseResource::versionFields(fn () => null)),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        /** @var User $user */
        $user = Auth::user();

        return app(CreateCourseAction::class)->handle(Arr::only($data, ['code', 'org_unit_id', 'is_active']), Arr::except($data, ['code', 'org_unit_id', 'is_active']), $user);
    }

    protected function getRedirectUrl(): string
    {
        return CourseResource::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
