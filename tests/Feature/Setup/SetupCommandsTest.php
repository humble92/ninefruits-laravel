<?php

use App\Domain\Institution\Settings\InstitutionSettings;
use App\Domain\Security\Actions\AssignRole;
use App\Domain\Security\Enums\Permission;
use App\Models\Security\Role;
use App\Models\User;
use Database\Seeders\Demo\DemoAccountSeeder;
use Database\Seeders\Essential\EssentialSeeder;

it('installs essential data and institution settings without questions', function () {
    $this->seed(EssentialSeeder::class);
    $admin = User::factory()->create();
    app(AssignRole::class)->handle($admin, Role::query()->where('name', 'system_administrator')->sole(), 'test');

    $this->artisan('setup:install', [
        '--force' => true,
        '--locale' => 'ko',
        '--timezone' => 'Asia/Seoul',
        '--institution-name' => '데모 대학교',
    ])->assertSuccessful();

    $settings = app(InstitutionSettings::class)->refresh();

    expect($settings->default_locale)->toBe('ko')
        ->and($settings->timezone)->toBe('Asia/Seoul')
        ->and($settings->name['ko'])->toBe('데모 대학교')
        ->and(Role::query()->where('name', 'registrar')->exists())->toBeTrue();
});

it('creates the first administrator interactively', function () {
    $this->seed(EssentialSeeder::class);

    $this->artisan('security:create-administrator', ['--email' => 'first@example.com', '--name' => 'First Admin'])
        ->expectsQuestion('Password', 'Correct-Horse-Battery-9')
        ->assertSuccessful();

    $user = User::query()->where('email', 'first@example.com')->sole();

    expect($user->can(Permission::SecuritySettingsManage->value))->toBeTrue();
});

it('refuses a non-interactive administrator without --password-stdin', function () {
    $this->seed(EssentialSeeder::class);

    $this->artisan('security:create-administrator', ['--email' => 'x@example.com', '--name' => 'X', '--no-interaction' => true])
        ->assertFailed();
});

it('keeps values administrators changed when essential seeders run again', function () {
    $this->seed(EssentialSeeder::class);
    $registrar = Role::query()->where('name', 'registrar')->sole();
    $registrar->replaceTranslations('display_name', ['en' => 'Student Records Office'])->save();
    $registrar->syncPermissions([]);

    $this->seed(EssentialSeeder::class);

    expect(Role::query()->where('name', 'registrar')->count())->toBe(1)
        ->and($registrar->fresh()->getTranslations('display_name'))->toBe(['en' => 'Student Records Office'])
        ->and($registrar->fresh()->permissions()->count())->toBe(0)
        ->and(Role::query()->where('name', 'system_administrator')->sole()->permissions()->count())
        ->toBe(count(Permission::cases()));
});

it('creates demo accounts and can run twice', function () {
    $this->seed(EssentialSeeder::class);

    $this->artisan('setup:demo', ['--force' => true])->assertSuccessful();
    $this->artisan('setup:demo', ['--force' => true])->assertSuccessful();

    foreach (DemoAccountSeeder::emails() as $email) {
        expect(User::query()->where('email', $email)->count())->toBe(1);
    }

    expect(User::query()->where('email', 'registrar@demo.test')->sole()->can(Permission::AdminPanelAccess->value))->toBeTrue();
});

it('never creates demo data in production', function () {
    app()->detectEnvironment(fn () => 'production');

    $this->artisan('setup:demo', ['--force' => true])->assertFailed();

    expect(User::query()->where('email', 'like', '%@demo.test')->exists())->toBeFalse();
});
