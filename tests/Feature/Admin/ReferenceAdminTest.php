<?php

use App\Domain\Institution\Settings\InstitutionSettings;
use App\Domain\Security\Actions\SyncPermissionCatalog;
use App\Domain\Security\Enums\MfaEnforcement;
use App\Domain\Security\Enums\Permission;
use App\Domain\Term\Enums\TermStatus;
use App\Domain\Term\Enums\TermWindowType;
use App\Filament\Admin\Resources\Reference\Buildings\Pages\ManageBuildings;
use App\Filament\Admin\Resources\Reference\Countries\Pages\ManageCountries;
use App\Filament\Admin\Resources\Reference\OrgUnits\OrgUnitResource;
use App\Filament\Admin\Resources\Reference\OrgUnits\Pages\CreateOrgUnit;
use App\Filament\Admin\Resources\Reference\OrgUnits\Pages\EditOrgUnit;
use App\Filament\Admin\Resources\Reference\Terms\Pages\EditTerm;
use App\Filament\Admin\Resources\Reference\Terms\RelationManagers\WindowsRelationManager;
use App\Filament\Admin\Resources\Reference\Terms\TermResource;
use App\Filament\Admin\Resources\Users\Pages\ViewUser;
use App\Filament\Admin\Resources\Users\RelationManagers\RoleAssignmentsRelationManager;
use App\Models\Facility\Building;
use App\Models\Facility\Room;
use App\Models\Institution\Country;
use App\Models\Organization\OrgUnit;
use App\Models\Organization\OrgUnitType;
use App\Models\Security\Role;
use App\Models\Term\Term;
use App\Models\User;
use Database\Seeders\Demo\DemoReferenceSeeder;
use Database\Seeders\Essential\EssentialSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    setSecuritySettings(['mfa_enforcement' => MfaEnforcement::Disabled]);
    $this->seed(EssentialSeeder::class);
    app(SyncPermissionCatalog::class)->handle();
    Filament::setCurrentPanel('admin');
    $this->manager = User::factory()->withPermissions(Permission::cases())->create();
});

it('renders every reference data page', function () {
    $this->seed(DemoReferenceSeeder::class);
    $pages = [
        '/admin/reference/countries', '/admin/reference/languages', '/admin/reference/lookup-types', '/admin/reference/org-unit-types',
        '/admin/reference/org-units', '/admin/reference/programs', '/admin/reference/buildings', '/admin/reference/rooms',
        '/admin/reference/term-types', '/admin/reference/academic-years', '/admin/reference/terms', '/admin/reference/time-slots',
        '/admin/reference/course-categories', '/admin/reference/grade-scales',
        OrgUnitResource::getUrl('edit', ['record' => OrgUnit::query()->where('code', 'CSE')->sole()]),
        TermResource::getUrl('edit', ['record' => Term::query()->first()]),
    ];

    foreach ($pages as $url) {
        $this->actingAs($this->manager)->get($url)->assertOk();
    }
});

it('lets viewers look but not change', function () {
    $viewer = User::factory()->withPermissions([Permission::AdminPanelAccess, Permission::ReferenceDataView])->create();
    $other = User::factory()->withPermissions([Permission::AdminPanelAccess])->create();

    Livewire::actingAs($viewer)->test(ManageCountries::class)->assertActionHidden('create');
    $this->actingAs($viewer)->get(OrgUnitResource::getUrl('create'))->assertForbidden();
    $this->actingAs($other)->get(OrgUnitResource::getUrl('index'))->assertForbidden();
});

it('creates an org unit with translated names', function () {
    $type = OrgUnitType::query()->where('code', 'department')->sole();

    Livewire::actingAs($this->manager)->test(CreateOrgUnit::class)
        ->fillForm([
            'code' => 'NURS',
            'org_unit_type_id' => $type->id,
            'name' => ['en' => 'Nursing', 'ko' => '간호학과'],
            'effective_start_date' => '2026-03-01',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(OrgUnit::query()->where('code', 'NURS')->sole()->getTranslations('name'))->toBe(['en' => 'Nursing', 'ko' => '간호학과']);
});

it('fills every translation when editing in a modal', function () {
    $country = Country::query()->where('code', 'KR')->sole();

    Livewire::actingAs($this->manager)->test(ManageCountries::class)
        ->mountAction(TestAction::make('edit')->table($country))
        ->assertSchemaStateSet(['name' => $country->getTranslations('name')])
        ->setActionData(['name' => ['en' => 'Korea, Republic of', 'ko' => '대한민국', 'zh_CN' => '']])
        ->callMountedAction()
        ->assertHasNoActionErrors();

    expect($country->fresh()->getTranslations('name'))->toBe(['en' => 'Korea, Republic of', 'ko' => '대한민국']);
});

it('explains instead of failing when deleting a building that has rooms', function () {
    $building = Building::factory()->create();
    Room::factory()->for($building)->create();

    Livewire::actingAs($this->manager)->test(ManageBuildings::class)
        ->callAction(TestAction::make('delete')->table($building))
        ->assertNotified(__('reference.errors.in_use_title'));

    expect($building->fresh())->not->toBeNull();
});

it('reorganizes an org unit from the edit page', function () {
    $old = OrgUnit::factory()->create(['effective_start_date' => '2000-03-01']);
    $new = OrgUnit::factory()->create();

    Livewire::actingAs($this->manager)->test(EditOrgUnit::class, ['record' => $old->getRouteKey()])
        ->callAction('reorganize', ['change_type' => 'merged', 'successor_ids' => $new->id, 'effective_date' => '2026-03-01', 'reason' => 'Merger'])
        ->assertHasNoActionErrors();

    expect($old->fresh()->effective_end_date->toDateString())->toBe('2026-02-28');
});

it('adds a staggered window with eligibility and changes the term status', function () {
    $term = Term::factory()->create();

    Livewire::actingAs($this->manager)
        ->test(WindowsRelationManager::class, ['ownerRecord' => $term, 'pageClass' => EditTerm::class])
        ->callAction(TestAction::make('create')->table(), [
            'window_type' => TermWindowType::Registration->value,
            'opens_at' => '2026-11-01 09:00',
            'closes_at' => '2026-11-05 18:00',
            'eligibility' => ['year_levels' => ['4'], 'admission_year_from' => '2022'],
        ])
        ->assertHasNoActionErrors();

    expect($term->windows()->sole()->eligibility->toArray())->toBe(['admission_year_from' => 2022, 'year_levels' => [4]]);

    Livewire::actingAs($this->manager)->test(EditTerm::class, ['record' => $term->getRouteKey()])
        ->callAction('changeStatus', ['status' => TermStatus::Published->value])
        ->assertHasNoActionErrors();

    expect($term->fresh()->status)->toBe(TermStatus::Published);
});

it('assigns a role scoped to an org unit from the picker', function () {
    $user = User::factory()->create();
    $unit = OrgUnit::factory()->create();
    $role = Role::factory()->create();

    Livewire::actingAs($this->manager)
        ->test(RoleAssignmentsRelationManager::class, ['ownerRecord' => $user, 'pageClass' => ViewUser::class])
        ->callAction(TestAction::make('assign')->table(), ['role_id' => $role->id, 'scope_type' => 'org_unit', 'scope_id' => $unit->id, 'reason' => 'Department assistant'])
        ->assertHasNoActionErrors();

    $assignment = $user->roleAssignments()->sole();

    expect($assignment->scope_id)->toBe($unit->id)
        ->and($assignment->describeScope())->toContain($unit->code);
});

it('enters window times in the institution time zone and stores them in UTC', function () {
    $settings = app(InstitutionSettings::class);
    $settings->timezone = 'Asia/Seoul';
    $settings->save();
    $term = Term::factory()->create();

    Livewire::actingAs($this->manager)
        ->test(WindowsRelationManager::class, ['ownerRecord' => $term, 'pageClass' => EditTerm::class])
        ->callAction(TestAction::make('create')->table(), [
            'window_type' => TermWindowType::Registration->value,
            'opens_at' => '2026-11-02 10:00',
            'closes_at' => '2026-11-05 18:00',
        ])
        ->assertHasNoActionErrors();

    expect($term->windows()->sole()->opens_at->utc()->format('Y-m-d H:i'))->toBe('2026-11-02 01:00');
});
