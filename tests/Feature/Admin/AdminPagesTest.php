<?php

use App\Domain\Security\Actions\AssignRole;
use App\Domain\Security\Actions\SyncPermissionCatalog;
use App\Domain\Security\Enums\MfaEnforcement;
use App\Domain\Security\Enums\Permission;
use App\Filament\Admin\Pages\ManageInstitutionSettings;
use App\Filament\Admin\Pages\ManageSecuritySettings;
use App\Filament\Admin\Resources\Activities\ActivityResource;
use App\Filament\Admin\Resources\RecordChanges\RecordChangeResource;
use App\Filament\Admin\Resources\Roles\RoleResource;
use App\Filament\Admin\Resources\SecurityEvents\SecurityEventResource;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\Security\Activity;
use App\Models\Security\RecordChange;
use App\Models\Security\Role;
use App\Models\User;

beforeEach(function () {
    setSecuritySettings(['mfa_enforcement' => MfaEnforcement::Disabled]);
    app(SyncPermissionCatalog::class)->handle();

    $this->admin = User::factory()->withPermissions(Permission::cases())->create();
    $this->subject = User::factory()->create();
    app(AssignRole::class)->handle($this->subject, Role::factory()->create(), 'Smoke test', assignedBy: $this->admin);

    $this->actingAs($this->admin)->post('/login', []); // records a security event
});

it('renders every admin page', function (string $url) {
    $this->actingAs($this->admin)->get($url)->assertOk();
})->with([
    'dashboard' => fn () => '/admin',
    'users' => fn () => UserResource::getUrl('index'),
    'user create' => fn () => UserResource::getUrl('create'),
    'user view' => fn () => UserResource::getUrl('view', ['record' => $this->subject]),
    'user edit' => fn () => UserResource::getUrl('edit', ['record' => $this->subject]),
    'roles' => fn () => RoleResource::getUrl('index'),
    'role create' => fn () => RoleResource::getUrl('create'),
    'role edit' => fn () => RoleResource::getUrl('edit', ['record' => Role::query()->firstOrFail()]),
    'activity log' => fn () => ActivityResource::getUrl('index'),
    'activity view' => fn () => ActivityResource::getUrl('view', ['record' => Activity::query()->firstOrFail()]),
    'record changes' => fn () => RecordChangeResource::getUrl('index'),
    'record change view' => fn () => RecordChangeResource::getUrl('view', ['record' => RecordChange::query()->where('table_name', 'users')->firstOrFail()]),
    'security events' => fn () => SecurityEventResource::getUrl('index'),
    'security settings' => fn () => ManageSecuritySettings::getUrl(),
    'institution settings' => fn () => ManageInstitutionSettings::getUrl(),
]);

it('renders the portal pages', function (string $url) {
    $this->actingAs($this->admin)->get($url)->assertOk();
})->with(['/portal', '/portal/security']);

it('renders the admin pages in Korean and Chinese', function (string $locale, string $text) {
    $this->admin->forceFill(['locale' => $locale])->save();

    $this->actingAs($this->admin)->get(UserResource::getUrl('index'))->assertOk()->assertSee($text);
})->with([
    ['ko', '사용자와 권한'],
    ['zh_CN', '用户与权限'],
]);
