<?php

use App\Domain\Admission\Enums\ApplicationStatus;
use App\Domain\Security\Actions\SyncPermissionCatalog;
use App\Domain\Security\Enums\MfaEnforcement;
use App\Domain\Security\Enums\Permission;
use App\Filament\Admin\Resources\Admissions\Applications\ApplicationResource;
use App\Filament\Admin\Resources\Admissions\Applications\Pages\ListApplications;
use App\Filament\Admin\Resources\Admissions\Cycles\AdmissionCycleResource;
use App\Filament\Admin\Resources\Admissions\Cycles\Pages\CreateAdmissionCycle;
use App\Models\Admission\AdmissionCycle;
use App\Models\Admission\Application;
use App\Models\Institution\LookupValue;
use App\Models\Organization\Program;
use App\Models\User;
use Database\Seeders\Demo\DemoAdmissionSeeder;
use Database\Seeders\Demo\DemoPeopleSeeder;
use Database\Seeders\Demo\DemoReferenceSeeder;
use Database\Seeders\Essential\EssentialSeeder;
use Database\Seeders\Profiles\KoreanUniversityProfileSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    setSecuritySettings(['mfa_enforcement' => MfaEnforcement::Disabled]);
    $this->seed([EssentialSeeder::class, KoreanUniversityProfileSeeder::class, DemoReferenceSeeder::class, DemoPeopleSeeder::class, DemoAdmissionSeeder::class]);
    app(SyncPermissionCatalog::class)->handle();
    Filament::setCurrentPanel('admin');
    $this->manager = User::factory()->withPermissions(Permission::cases())->create();
});

it('renders the admission pages', function () {
    $pages = [
        AdmissionCycleResource::getUrl('index'), AdmissionCycleResource::getUrl('create'),
        AdmissionCycleResource::getUrl('edit', ['record' => AdmissionCycle::query()->first()]),
        ApplicationResource::getUrl('index'),
        ApplicationResource::getUrl('view', ['record' => Application::query()->where('status', '!=', 'draft')->first()]),
        '/admin/settings/admissions',
    ];

    foreach ($pages as $url) {
        $this->actingAs($this->manager)->get($url)->assertOk();
    }
});

it('creates a cycle with programs and documents', function () {
    $program = Program::query()->effectiveOn()->first();
    $document = LookupValue::query()->ofType(Application::DOCUMENT_TYPE_LOOKUP)->first();

    Livewire::actingAs($this->manager)->test(CreateAdmissionCycle::class)
        ->fillForm([
            'code' => '2028-SPRING',
            'name' => ['en' => '2028 spring admission', 'ko' => '2028학년도 1학기 모집'],
            'opens_at' => now()->toDateTimeString(),
            'is_published' => true,
            'programs' => [['program_id' => $program->id, 'capacity' => 40]],
            'documentTypes' => [['document_type_id' => $document->id, 'is_required' => true]],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $cycle = AdmissionCycle::query()->where('code', '2028-SPRING')->sole();
    expect($cycle->isRolling())->toBeTrue()
        ->and($cycle->programs()->value('capacity'))->toBe(40)
        ->and($cycle->documentTypes()->count())->toBe(1);
});

it('lists submitted applications but never drafts, and releases decisions in bulk', function () {
    $draft = Application::query()->where('status', ApplicationStatus::Draft)->first();
    $admitted = Application::query()->where('status', ApplicationStatus::Admitted)->whereNull('decision_released_at')->get();

    Livewire::actingAs($this->manager)->test(ListApplications::class)
        ->assertCanNotSeeTableRecords([$draft])
        ->selectTableRecords($admitted->modelKeys())
        ->callAction(TestAction::make('releaseDecisions')->table()->bulk());

    expect(Application::query()->whereIn('id', $admitted->modelKeys())->whereNull('decision_released_at')->count())->toBe(0);
});

it('shows open cycles publicly and the applicant their applications', function () {
    $this->get('/admissions')->assertOk()->assertSee(AdmissionCycle::query()->where('code', 'DEMO-ROLLING')->sole()->name);

    $applicant = User::query()->where('email', 'admitted@demo.test')->sole();
    $application = Application::query()->where('person_id', $applicant->person_id)->sole();

    $this->actingAs($applicant)->get('/portal/applications')->assertOk()->assertSee(ApplicationStatus::Admitted->label());
    $this->actingAs($applicant)->get('/portal/applications/'.$application->id)->assertOk()->assertSee(__('admissions.portal.accept'));

    $other = Application::query()->where('person_id', '!=', $applicant->person_id)->first();
    $this->actingAs($applicant)->get('/portal/applications/'.$other->id)->assertNotFound();
});
