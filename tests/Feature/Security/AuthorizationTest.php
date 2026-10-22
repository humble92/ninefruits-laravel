<?php

use App\Domain\Security\Actions\AssignRole;
use App\Domain\Security\Actions\RevokeRoleAssignment;
use App\Domain\Security\Contracts\PersonaDetector;
use App\Domain\Security\Enums\MfaEnforcement;
use App\Domain\Security\Enums\Permission;
use App\Domain\Security\Enums\Persona;
use App\Domain\Security\Enums\ScopeType;
use App\Domain\Security\Services\AuthorizationService;
use App\Domain\Security\Services\PersonaResolver;
use App\Models\Organization\OrgUnit;
use App\Models\Organization\Program;
use App\Models\Security\Activity;
use App\Models\Security\Role;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    setSecuritySettings(['mfa_enforcement' => MfaEnforcement::Disabled]);
});

it('denies the admin panel without a role', function () {
    $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
});

it('grants and removes access by changing role assignments only', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create();
    $role = Role::factory()->withPermissions([Permission::AdminPanelAccess, Permission::UsersView])->create();

    $assignment = app(AssignRole::class)->handle($user, $role, 'New registrar', assignedBy: $admin);

    $this->actingAs($user)->get('/admin')->assertOk();
    $this->actingAs($user)->get('/admin/users')->assertOk();
    $this->actingAs($user)->get('/admin/roles')->assertForbidden();

    app(RevokeRoleAssignment::class)->handle($assignment, 'Left the office', $admin);

    $this->actingAs($user)->get('/admin')->assertForbidden();
});

it('opens a page as soon as the role gains the permission', function () {
    $user = User::factory()->withPermissions([Permission::AdminPanelAccess])->create();

    $this->actingAs($user)->get('/admin/roles')->assertForbidden();

    $role = $user->roleAssignments()->firstOrFail()->role()->firstOrFail();
    $role->givePermissionTo(App\Models\Security\Permission::findOrCreate(Permission::RolesView->value, 'web'));
    app(AuthorizationService::class)->flush();

    $this->actingAs($user)->get('/admin/roles')->assertOk();
});

it('shows only the menu entries the user is allowed to use', function (array $permissions, bool $seesRoles) {
    $user = User::factory()->withPermissions([Permission::AdminPanelAccess, ...$permissions])->create();

    $response = $this->actingAs($user)->get('/admin')->assertOk();

    $seesRoles ? $response->assertSee('/admin/roles') : $response->assertDontSee('/admin/roles');
})->with([
    'without roles.view' => [[], false],
    'with roles.view' => [[Permission::RolesView], true],
]);

it('ignores assignments outside their validity period', function () {
    $user = User::factory()->create();
    $role = Role::factory()->withPermissions([Permission::UsersView])->create();
    $now = CarbonImmutable::now();

    app(AssignRole::class)->handle($user, $role, 'future', startsAt: $now->addDay());
    app(AssignRole::class)->handle($user, $role, 'expired', startsAt: $now->subDays(10), endsAt: $now->subDay());

    expect(app(AuthorizationService::class)->hasPermission($user, Permission::UsersView))->toBeFalse();

    $this->travel(2)->days();
    app(AuthorizationService::class)->flush();

    expect(app(AuthorizationService::class)->hasPermission($user, Permission::UsersView))->toBeTrue();
});

it('merges scopes from several assignments and includes units below an org unit', function () {
    $user = User::factory()->create();
    $role = Role::factory()->withPermissions([Permission::UsersView])->create();
    $college = OrgUnit::factory()->create();
    $department = OrgUnit::factory()->under($college)->create();
    $other = OrgUnit::factory()->create();
    $program = Program::factory()->create();

    app(AssignRole::class)->handle($user, $role, 'college', ScopeType::OrgUnit, $college->id);
    app(AssignRole::class)->handle($user, $role, 'other unit', ScopeType::OrgUnit, $other->id);
    app(AssignRole::class)->handle($user, $role, 'program', ScopeType::Program, $program->id);

    $scope = app(AuthorizationService::class)->scopeFor($user, Permission::UsersView);

    expect($scope->isGlobal)->toBeFalse()
        ->and($scope->orgUnitIds)->toEqualCanonicalizing([$college->id, $department->id, $other->id])
        ->and($scope->programIds)->toBe([$program->id]);
});

it('gives deactivated users no permissions', function () {
    $user = User::factory()->withPermissions([Permission::UsersView])->create();
    $user->forceFill(['is_active' => false])->save();
    app(AuthorizationService::class)->flush();

    expect($user->can(Permission::UsersView->value))->toBeFalse();
});

it('grants persona permissions from data, limited to own records', function () {
    Role::factory()->persona(Persona::Student)->withPermissions([Permission::UsersView])->create();
    $student = User::factory()->create(['email' => 'student@example.com']);

    app()->forgetScopedInstances();
    app()->instance(PersonaResolver::class, new PersonaResolver([
        new class implements PersonaDetector
        {
            public function persona(): Persona
            {
                return Persona::Student;
            }

            public function appliesTo(User $user): bool
            {
                return $user->email === 'student@example.com';
            }
        },
    ]));

    $scope = app(AuthorizationService::class)->scopeFor($student, Permission::UsersView);

    expect($scope->includesOwn)->toBeTrue()
        ->and($scope->isGlobal)->toBeFalse()
        ->and(app(AuthorizationService::class)->hasPermission(User::factory()->create(), Permission::UsersView))->toBeFalse();
});

it('does not allow assigning persona roles by hand', function () {
    $role = Role::factory()->persona(Persona::Student)->create();

    app(AssignRole::class)->handle(User::factory()->create(), $role, 'manual');
})->throws(ValidationException::class);

it('audits role assignments with the reason', function () {
    $admin = User::factory()->create();
    $role = Role::factory()->create();

    $assignment = app(AssignRole::class)->handle(User::factory()->create(), $role, 'Covering for a colleague', assignedBy: $admin);

    $activity = Activity::query()->where('event', 'role.assigned')->sole();

    expect($activity->reason)->toBe('Covering for a colleague')
        ->and($activity->causer_id)->toBe($admin->id)
        ->and($activity->subject_id)->toBe($assignment->id);
});

it('stops users from ending their own assignments', function () {
    $admin = User::factory()->withPermissions([Permission::RoleAssignmentsManage])->create();
    $own = $admin->roleAssignments()->firstOrFail();

    expect($admin->can('revoke', $own))->toBeFalse();
});
