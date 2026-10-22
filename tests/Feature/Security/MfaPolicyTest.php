<?php

use App\Domain\Security\Actions\AssignRole;
use App\Domain\Security\Enums\MfaEnforcement;
use App\Domain\Security\Enums\Permission;
use App\Models\Security\Role;
use App\Models\User;

it('only reminds users when two-factor authentication is recommended', function () {
    setSecuritySettings(['mfa_enforcement' => MfaEnforcement::Recommended]);

    $this->actingAs(User::factory()->create())
        ->get('/portal')
        ->assertOk()
        ->assertSee(__('security.mfa.recommended'));
});

it('shows nothing when two-factor authentication is not used', function () {
    setSecuritySettings(['mfa_enforcement' => MfaEnforcement::Disabled]);

    $this->actingAs(User::factory()->create())
        ->get('/portal')
        ->assertOk()
        ->assertDontSee(__('security.mfa.recommended'));
});

it('lets users work during the grace period and then requires enrollment', function () {
    setSecuritySettings(['mfa_enforcement' => MfaEnforcement::RequiredForAll, 'mfa_grace_period_days' => 7]);
    $user = User::factory()->create();

    $this->actingAs($user)->get('/portal')->assertOk();

    expect($user->fresh()->mfa_grace_started_at)->not->toBeNull();

    $this->travel(8)->days();

    $this->actingAs($user->fresh())->get('/portal')->assertRedirect(route('portal.security'));
    $this->actingAs($user->fresh())->get(route('portal.security'))->assertOk();
});

it('applies a policy change on the next request', function () {
    setSecuritySettings(['mfa_enforcement' => MfaEnforcement::Recommended, 'mfa_grace_period_days' => 0]);
    $user = User::factory()->create();

    $this->actingAs($user)->get('/portal')->assertOk();

    setSecuritySettings(['mfa_enforcement' => MfaEnforcement::RequiredForAll]);

    $this->actingAs($user)->get('/portal')->assertRedirect(route('portal.security'));
});

it('requires two-factor authentication only for the selected roles', function () {
    $role = Role::factory()->withPermissions([Permission::AdminPanelAccess])->create();
    setSecuritySettings([
        'mfa_enforcement' => MfaEnforcement::RequiredForRoles,
        'mfa_required_role_ids' => [$role->id],
        'mfa_grace_period_days' => 0,
    ]);

    $staff = User::factory()->create();
    app(AssignRole::class)->handle($staff, $role, 'test');
    $other = User::factory()->create();

    $this->actingAs($staff)->get('/portal')->assertRedirect(route('portal.security'));
    $this->actingAs($other)->get('/portal')->assertOk();
});

it('blocks the admin panel until enrollment', function () {
    setSecuritySettings(['mfa_enforcement' => MfaEnforcement::RequiredForAll, 'mfa_grace_period_days' => 0]);
    $user = User::factory()->withPermissions([Permission::AdminPanelAccess])->create();

    $this->actingAs($user)->get('/admin')->assertRedirect(route('portal.security'));
});

it('lets enrolled users through', function () {
    setSecuritySettings(['mfa_enforcement' => MfaEnforcement::RequiredForAll, 'mfa_grace_period_days' => 0]);

    $this->actingAs(User::factory()->withMfa()->create())->get('/portal')->assertOk();
});
