<?php

use App\Domain\Person\Enums\StatusChangeStatus;
use App\Domain\Person\Enums\StudentStatus;
use App\Domain\Security\Actions\SyncPermissionCatalog;
use App\Domain\Security\Enums\MfaEnforcement;
use App\Domain\Security\Enums\Permission;
use App\Domain\Security\Enums\ScopeType;
use App\Filament\Admin\Resources\Instructors\InstructorResource;
use App\Filament\Admin\Resources\People\PersonResource;
use App\Filament\Admin\Resources\Reference\StatusChangeTypes\StatusChangeTypeResource;
use App\Filament\Admin\Resources\StatusChanges\Pages\ListStatusChanges;
use App\Filament\Admin\Resources\StatusChanges\StatusChangeResource;
use App\Filament\Admin\Resources\StudentRecords\StudentRecordResource;
use App\Filament\Admin\Resources\Users\Pages\ViewUser;
use App\Models\Person\Instructor;
use App\Models\Person\Person;
use App\Models\Person\StudentRecord;
use App\Models\Person\StudentStatusChange;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\Demo\DemoPeopleSeeder;
use Database\Seeders\Demo\DemoReferenceSeeder;
use Database\Seeders\Essential\EssentialSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    setSecuritySettings(['mfa_enforcement' => MfaEnforcement::Disabled]);
    $this->seed([EssentialSeeder::class, DemoReferenceSeeder::class, DemoPeopleSeeder::class]);
    app(SyncPermissionCatalog::class)->handle();
    Filament::setCurrentPanel('admin');
    $this->manager = User::factory()->withPermissions(Permission::cases())->create();
});

it('renders every people and student record page', function () {
    $record = StudentRecord::query()->first();
    $pages = [
        PersonResource::getUrl('index'), PersonResource::getUrl('create'),
        PersonResource::getUrl('view', ['record' => $record->person_id]), PersonResource::getUrl('edit', ['record' => $record->person_id]),
        StudentRecordResource::getUrl('index'), StudentRecordResource::getUrl('create'),
        StudentRecordResource::getUrl('view', ['record' => $record]), StudentRecordResource::getUrl('edit', ['record' => $record]),
        StatusChangeResource::getUrl('index'), StatusChangeTypeResource::getUrl('index'),
        InstructorResource::getUrl('index'), InstructorResource::getUrl('create'),
        InstructorResource::getUrl('edit', ['record' => Instructor::query()->first()]),
        '/admin/settings/authentication', '/admin/settings/student-records',
    ];

    foreach ($pages as $url) {
        $this->actingAs($this->manager)->get($url)->assertOk();
    }
});

it('limits a department assistant to records of their department', function () {
    $record = StudentRecord::query()->first();
    $other = StudentRecord::query()->where('org_unit_id', '!=', $record->org_unit_id)->first();
    $assistant = User::factory()->withPermissions([Permission::AdminPanelAccess, Permission::StudentRecordsView], ScopeType::OrgUnit, $record->org_unit_id)->create();

    $this->actingAs($assistant)->get(StudentRecordResource::getUrl('view', ['record' => $record]))->assertOk();
    $this->actingAs($assistant)->get(StudentRecordResource::getUrl('view', ['record' => $other]))->assertNotFound();
    $this->actingAs($assistant)->get(StudentRecordResource::getUrl('create'))->assertForbidden();
    $this->actingAs($assistant)->get(PersonResource::getUrl('create'))->assertForbidden();
});

it('approves a pending request from the status change list', function () {
    $change = StudentStatusChange::query()->where('status', StatusChangeStatus::Requested)->first();

    Livewire::actingAs($this->manager)->test(ListStatusChanges::class)
        ->callTableAction('approve', $change, ['note' => 'Documents checked'])
        ->assertHasNoTableActionErrors();

    expect($change->fresh()->status)->toBe(StatusChangeStatus::Approved)
        ->and($change->studentRecord->fresh()->status)->toBe(StudentStatus::OnLeave);
});

it('unlocks a locked account with a reason', function () {
    $locked = User::factory()->create(['locked_until' => CarbonImmutable::now()->addMinutes(10)]);

    Livewire::actingAs($this->manager)->test(ViewUser::class, ['record' => $locked->getKey()])
        ->callAction('unlock', ['reason' => 'Verified by phone'])
        ->assertHasNoActionErrors();

    expect($locked->fresh()->isLocked())->toBeFalse();
});

it('shows people only to staff allowed to see them', function () {
    $nobody = User::factory()->withPermissions([Permission::AdminPanelAccess])->create();

    $this->actingAs($nobody)->get(PersonResource::getUrl('index'))->assertForbidden();
    expect(Person::query()->count())->toBeGreaterThan(0);
});
