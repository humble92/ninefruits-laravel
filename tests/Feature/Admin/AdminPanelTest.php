<?php

use App\Domain\Security\Actions\SyncPermissionCatalog;
use App\Domain\Security\Enums\MfaEnforcement;
use App\Domain\Security\Enums\Permission;
use App\Domain\Security\Enums\SecurityEventType;
use App\Domain\Security\Settings\SecuritySettings;
use App\Filament\Admin\Pages\ManageSecuritySettings;
use App\Filament\Admin\Resources\Roles\Pages\CreateRole;
use App\Filament\Admin\Resources\Roles\Pages\EditRole;
use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use App\Filament\Admin\Resources\Users\Pages\ViewUser;
use App\Filament\Admin\Resources\Users\RelationManagers\RoleAssignmentsRelationManager;
use App\Models\Security\Activity;
use App\Models\Security\Role;
use App\Models\Security\SecurityEvent;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    setSecuritySettings(['mfa_enforcement' => MfaEnforcement::Disabled]);
    app(SyncPermissionCatalog::class)->handle();
    Filament::setCurrentPanel('admin');
});

function administrator(): User
{
    return User::factory()->withPermissions(Permission::cases())->create();
}

it('lists users for staff with users.view', function () {
    $users = User::factory()->count(3)->create();

    Livewire::actingAs(administrator())
        ->test(ListUsers::class)
        ->assertCanSeeTableRecords($users);
});

it('creates a role with translated names and permissions', function () {
    Livewire::actingAs(administrator())
        ->test(CreateRole::class)
        ->fillForm([
            'name' => 'exam_office',
            'display_name' => ['en' => 'Exam Office', 'ko' => '시험 담당'],
            'permissions' => ['users' => [Permission::UsersView->value]],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $role = Role::query()->where('name', 'exam_office')->sole();

    expect($role->getTranslations('display_name'))->toBe(['en' => 'Exam Office', 'ko' => '시험 담당'])
        ->and($role->permissions()->pluck('name')->all())->toBe([Permission::UsersView->value])
        ->and(Activity::query()->where('event', 'role.permissions_changed')->exists())->toBeTrue();
});

it('requires the fallback locale display name', function () {
    Livewire::actingAs(administrator())
        ->test(CreateRole::class)
        ->fillForm(['name' => 'no_english', 'display_name' => ['ko' => '영문 없음']])
        ->call('create')
        ->assertHasFormErrors(['display_name.en' => 'required']);
});

it('removes a translation when its field is cleared', function () {
    $role = Role::factory()->create(['display_name' => ['en' => 'Registrar', 'ko' => '학적 담당']]);

    Livewire::actingAs(administrator())
        ->test(EditRole::class, ['record' => $role->getRouteKey()])
        ->fillForm(['display_name' => ['en' => 'Registrar', 'ko' => '']])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($role->fresh()->getTranslations('display_name'))->toBe(['en' => 'Registrar']);
});

it('updates role permissions and records what changed', function () {
    $role = Role::factory()->withPermissions([Permission::UsersView])->create();

    Livewire::actingAs(administrator())
        ->test(EditRole::class, ['record' => $role->getRouteKey()])
        ->assertFormSet(['permissions.users' => [Permission::UsersView->value]])
        ->fillForm(['permissions' => ['users' => [Permission::UsersCreate->value]]])
        ->call('save')
        ->assertHasNoFormErrors();

    $activity = Activity::query()->where('event', 'role.permissions_changed')->latest('id')->first();

    expect($role->permissions()->pluck('name')->all())->toBe([Permission::UsersCreate->value])
        ->and($activity->properties['added'])->toBe([Permission::UsersCreate->value])
        ->and($activity->properties['removed'])->toBe([Permission::UsersView->value]);
});

it('assigns a role from the user page with a reason', function () {
    $user = User::factory()->create();
    $role = Role::factory()->create();

    Livewire::actingAs(administrator())
        ->test(RoleAssignmentsRelationManager::class, ['ownerRecord' => $user, 'pageClass' => ViewUser::class])
        ->callAction(TestAction::make('assign')->table(), [
            'role_id' => $role->id,
            'scope_type' => 'global',
            'reason' => 'Semester coverage',
        ])
        ->assertHasNoFormErrors();

    expect($user->roleAssignments()->sole()->reason)->toBe('Semester coverage');
});

it('resets two-factor authentication with a reason', function () {
    $user = User::factory()->withMfa()->create();

    Livewire::actingAs(administrator())
        ->test(ViewUser::class, ['record' => $user->getRouteKey()])
        ->callAction('resetMfa', ['reason' => 'Lost phone'])
        ->assertHasNoFormErrors();

    expect($user->fresh()->two_factor_confirmed_at)->toBeNull()
        ->and(SecurityEvent::query()->where('type', SecurityEventType::MfaReset)->sole()->properties['reason'])->toBe('Lost phone');
});

it('does not reset two-factor authentication without permission', function () {
    $user = User::factory()->withMfa()->create();
    $viewer = User::factory()->withPermissions([Permission::AdminPanelAccess, Permission::UsersView])->create();

    Livewire::actingAs($viewer)
        ->test(ViewUser::class, ['record' => $user->getRouteKey()])
        ->assertActionHidden('resetMfa');
});

it('changes the two-factor policy from the security settings page', function () {
    $role = Role::factory()->create();

    Livewire::actingAs(administrator())
        ->test(ManageSecuritySettings::class)
        ->fillForm([
            'mfa_enforcement' => MfaEnforcement::RequiredForRoles->value,
            'mfa_required_role_ids' => [$role->id],
            'mfa_grace_period_days' => 3,
            'mfa_remember_device_days' => 0,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $settings = app(SecuritySettings::class)->refresh();

    expect($settings->mfa_enforcement)->toBe(MfaEnforcement::RequiredForRoles)
        ->and($settings->mfa_required_role_ids)->toBe([$role->id])
        ->and(Activity::query()->where('event', 'settings.updated')->exists())->toBeTrue();
});

it('hides the security settings page without permission', function () {
    $user = User::factory()->withPermissions([Permission::AdminPanelAccess])->create();

    $this->actingAs($user)->get(ManageSecuritySettings::getUrl())->assertForbidden();
});
