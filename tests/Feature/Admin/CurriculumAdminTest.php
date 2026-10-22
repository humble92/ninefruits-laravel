<?php

use App\Domain\Offering\Enums\SectionStatus;
use App\Domain\Person\Enums\AdditionalProgramRequestStatus;
use App\Domain\Security\Actions\SyncPermissionCatalog;
use App\Domain\Security\Enums\MfaEnforcement;
use App\Domain\Security\Enums\Permission;
use App\Filament\Admin\Pages\ViewTimetables;
use App\Filament\Admin\Resources\AdditionalProgramRequests\AdditionalProgramRequestResource;
use App\Filament\Admin\Resources\AdditionalProgramRequests\Pages\ListAdditionalProgramRequests;
use App\Filament\Admin\Resources\Curriculum\Courses\CourseResource;
use App\Filament\Admin\Resources\Curriculum\Requirements\CurriculumRequirementSetResource;
use App\Filament\Admin\Resources\Offering\Sections\Pages\CreateSection;
use App\Filament\Admin\Resources\Offering\Sections\Pages\EditSection;
use App\Filament\Admin\Resources\Offering\Sections\RelationManagers\MeetingsRelationManager;
use App\Filament\Admin\Resources\Offering\Sections\SectionResource;
use App\Filament\Admin\Resources\Reference\Terms\TermResource;
use App\Livewire\Portal\MyRecords;
use App\Models\Curriculum\Course;
use App\Models\Curriculum\CurriculumRequirementSet;
use App\Models\Offering\Section;
use App\Models\Organization\Program;
use App\Models\Person\AdditionalProgramRequest;
use App\Models\Term\Term;
use App\Models\User;
use Database\Seeders\Demo\DemoAccountSeeder;
use Database\Seeders\Demo\DemoCurriculumSeeder;
use Database\Seeders\Demo\DemoPeopleSeeder;
use Database\Seeders\Demo\DemoReferenceSeeder;
use Database\Seeders\Essential\EssentialSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    setSecuritySettings(['mfa_enforcement' => MfaEnforcement::Disabled]);
    $this->seed([EssentialSeeder::class, DemoAccountSeeder::class, DemoReferenceSeeder::class, DemoPeopleSeeder::class, DemoCurriculumSeeder::class]);
    app(SyncPermissionCatalog::class)->handle();
    Filament::setCurrentPanel('admin');
    $this->manager = User::factory()->withPermissions(Permission::cases())->create();
    $this->section = Section::query()->where('status', SectionStatus::Published)->with('meetings')->first();
});

it('renders the curriculum and section pages', function () {
    $pages = [
        CourseResource::getUrl('index'), CourseResource::getUrl('create'), CourseResource::getUrl('edit', ['record' => Course::query()->first()]),
        CurriculumRequirementSetResource::getUrl('index'), CurriculumRequirementSetResource::getUrl('create'),
        CurriculumRequirementSetResource::getUrl('edit', ['record' => CurriculumRequirementSet::query()->first()]),
        SectionResource::getUrl('index'), SectionResource::getUrl('create'), SectionResource::getUrl('edit', ['record' => $this->section]),
        '/admin/offering/timetables', AdditionalProgramRequestResource::getUrl('index'),
        TermResource::getUrl('edit', ['record' => $this->section->term_id]),
    ];

    foreach ($pages as $url) {
        $this->actingAs($this->manager)->get($url)->assertOk();
    }
});

it('creates a section and explains a room conflict on the meetings tab', function () {
    $taken = $this->section->meetings->first();
    $course = Course::query()->whereDoesntHave('versions', fn ($version) => $version->whereNotNull('effective_end_date'))->where('is_active', true)->first();

    Livewire::actingAs($this->manager)->test(CreateSection::class)
        ->fillForm(['term_session_id' => $this->section->term->fullTermSession()->id, 'course_id' => $course->id, 'section_code' => '99', 'capacity' => 30, 'org_unit_id' => $course->org_unit_id])
        ->call('create')
        ->assertHasNoFormErrors();

    $created = Section::query()->where('section_code', '99')->sole();

    Livewire::actingAs($this->manager)->test(MeetingsRelationManager::class, ['ownerRecord' => $created, 'pageClass' => EditSection::class])
        ->callTableAction('create', data: ['weekday' => $taken->weekday, 'start_time' => substr($taken->start_time, 0, 5), 'end_time' => substr($taken->end_time, 0, 5), 'room_id' => $taken->room_id])
        ->assertNotified(__('offering.errors.room_conflict', ['section' => $this->section->label().' ('.$this->section->term->code.')']));

    expect($created->meetings()->count())->toBe(0);
});

it('shows a room timetable', function () {
    $taken = $this->section->meetings->first();

    Livewire::actingAs($this->manager)->test(ViewTimetables::class)
        ->set('data.term_id', $this->section->term_id)
        ->set('data.mode', 'room')
        ->set('data.target_id', $taken->room_id)
        ->assertSee($this->section->label());
});

it('approves a second major application from the queue', function () {
    $request = AdditionalProgramRequest::query()->where('status', AdditionalProgramRequestStatus::Requested)->first();

    Livewire::actingAs($this->manager)->test(ListAdditionalProgramRequests::class)
        ->callTableAction('approve', $request, ['note' => 'Welcome'])
        ->assertHasNoTableActionErrors();

    expect($request->refresh()->status)->toBe(AdditionalProgramRequestStatus::Approved);
});

it('lets students browse the catalog and instructors see their teaching', function () {
    $student = User::query()->where('email', 'student@demo.test')->sole();
    $draft = Section::factory()->inTerm($this->section->term)->create(['section_code' => 'DR']);

    $this->actingAs($student)->get(route('portal.catalog', ['termId' => $this->section->term_id]))->assertOk()->assertSee($this->section->label())->assertDontSee($draft->label());
    $this->actingAs($student)->get(route('portal.sections.show', $this->section))->assertOk();
    $this->actingAs($student)->get(route('portal.sections.show', $draft))->assertNotFound();
    $this->actingAs($student)->get(route('portal.syllabus.edit', $this->section))->assertForbidden();

    $instructor = User::query()->where('email', 'instructor@demo.test')->sole();
    $taught = Section::query()->taughtBy($instructor->person_id)->where('term_id', $this->section->term_id)->first();
    $this->actingAs($instructor)->get(route('portal.teaching', ['termId' => $this->section->term_id]))->assertOk()->assertSee($taught->label());
    $this->actingAs($instructor)->get(route('portal.syllabus.edit', $taught))->assertOk();
});

it('lets a student apply for a minor in the open window', function () {
    $student = User::query()->where('email', 'student@demo.test')->sole();
    $record = $student->person->studentRecords()->sole();
    $target = Program::query()->where('allows_minor', true)->whereKeyNot($record->program_id)->first();

    Livewire::actingAs($student)->test(MyRecords::class)
        ->assertSee(__('portal.records.apply_additional_program'))
        ->set('additionalRole', 'minor')
        ->set('additionalProgramId', $target->id)
        ->call('applyAdditionalProgram')
        ->assertHasNoErrors();

    expect(AdditionalProgramRequest::query()->where('student_record_id', $record->id)->sole()->term_id)
        ->toBe(Term::query()->where('start_date', '>', now()->toDateString())->orderBy('start_date')->value('id'));
});
