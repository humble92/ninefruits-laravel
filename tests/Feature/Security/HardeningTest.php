<?php

use App\Actions\Fortify\UpdateUserPassword;
use App\Domain\Reporting\Services\ReportCatalog;
use App\Domain\Security\Actions\AssignRole;
use App\Domain\Security\Actions\UpdateUserAccount;
use App\Domain\Security\Enums\MfaEnforcement;
use App\Domain\Security\Enums\Permission;
use App\Domain\Security\Enums\ScopeType;
use App\Domain\Security\Enums\SecurityEventType;
use App\Models\Organization\OrgUnit;
use App\Models\Person\Person;
use App\Models\Security\Role;
use App\Models\Security\SecurityEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/*
 * Fixes from the security review of 2026-10-23 (porting/phase-13-report.md).
 */

beforeEach(function () {
    setSecuritySettings(['mfa_enforcement' => MfaEnforcement::Disabled]);
});

it('refuses role assignments to oneself, above one\'s own permissions, or outside one\'s scope', function () {
    $actor = User::factory()->withPermissions([Permission::AdminPanelAccess, Permission::RoleAssignmentsManage, Permission::UsersView])->create();
    $target = User::factory()->create();
    $small = Role::factory()->withPermissions([Permission::UsersView])->create();
    $big = Role::factory()->withPermissions([Permission::UsersView, Permission::RolesManage])->create();

    expect(fn () => app(AssignRole::class)->handle($actor, $small, 'me', assignedBy: $actor))->toThrow(ValidationException::class)
        ->and(fn () => app(AssignRole::class)->handle($target, $big, 'escalation', assignedBy: $actor))->toThrow(ValidationException::class);
    app(AssignRole::class)->handle($target, $small, 'fine', assignedBy: $actor);

    $unit = OrgUnit::factory()->create();
    $other = OrgUnit::factory()->create();
    $dean = User::factory()->withPermissions([Permission::RoleAssignmentsManage, Permission::UsersView], ScopeType::OrgUnit, $unit->id)->create();

    expect(fn () => app(AssignRole::class)->handle($target, $small, 'global', assignedBy: $dean))->toThrow(ValidationException::class)
        ->and(fn () => app(AssignRole::class)->handle($target, $small, 'elsewhere', ScopeType::OrgUnit, $other->id, assignedBy: $dean))->toThrow(ValidationException::class);
    app(AssignRole::class)->handle($target, $small, 'own unit', ScopeType::OrgUnit, $unit->id, assignedBy: $dean);
});

it('resets verification, ends sessions and records an event when staff change an email', function () {
    $admin = User::factory()->withPermissions(Permission::cases())->create();
    $user = User::factory()->create(['email_verified_at' => now()]);
    DB::table('sessions')->insert(['id' => 'abc', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);

    app(UpdateUserAccount::class)->handle($user, ['email' => 'new@example.com', 'is_active' => true], $admin);

    expect($user->refresh()->email)->toBe('new@example.com')->and($user->email_verified_at)->toBeNull()
        ->and(DB::table('sessions')->where('user_id', $user->id)->exists())->toBeFalse()
        ->and(SecurityEvent::query()->where('type', SecurityEventType::EmailChanged)->where('user_id', $user->id)->exists())->toBeTrue();
});

it('stops staff deactivating themselves or editing a more privileged account', function () {
    $admin = User::factory()->withPermissions(Permission::cases())->create();
    $clerk = User::factory()->withPermissions([Permission::AdminPanelAccess, Permission::UsersUpdate])->create();

    expect(fn () => app(UpdateUserAccount::class)->handle($admin, ['is_active' => false], $admin))->toThrow(ValidationException::class)
        ->and(fn () => app(UpdateUserAccount::class)->handle($admin, ['name' => 'Renamed'], $clerk))->toThrow(ValidationException::class);
    app(UpdateUserAccount::class)->handle($clerk, ['name' => 'Renamed'], $admin);
    expect($clerk->refresh()->name)->toBe('Renamed');
});

it('signs a deactivated user out on the next request', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/portal')->assertOk();
    $user->forceFill(['is_active' => false])->save();
    $this->actingAs($user)->get('/portal')->assertRedirect('/login');
    expect(auth()->check())->toBeFalse();
});

it('rotates the remember token and records an event when the password changes', function () {
    $user = User::factory()->create(['password' => 'Old-password-1!', 'remember_token' => 'before']);

    $this->actingAs($user);
    app(UpdateUserPassword::class)->update($user, ['current_password' => 'Old-password-1!', 'password' => 'New-password-1!', 'password_confirmation' => 'New-password-1!']);

    expect($user->refresh()->remember_token)->not->toBe('before')
        ->and(SecurityEvent::query()->where('type', SecurityEventType::PasswordChanged)->where('user_id', $user->id)->exists())->toBeTrue();
});

it('neutralises spreadsheet formulas in CSV cells and keeps encrypted identifiers out of arrays', function () {
    expect(ReportCatalog::cell('=1+1'))->toBe("'=1+1")->and(ReportCatalog::cell('+123'))->toBe("'+123")
        ->and(ReportCatalog::cell('-5'))->toBe("'-5")->and(ReportCatalog::cell('@x'))->toBe("'@x")
        ->and(ReportCatalog::cell('plain'))->toBe('plain')->and(ReportCatalog::cell(null))->toBe('');

    $person = Person::factory()->withNationalId('900101-1234567')->create();
    expect($person->toArray())->not->toHaveKeys(['national_id_encrypted', 'national_id_hash', 'passport_number_encrypted', 'passport_number_hash']);
});
