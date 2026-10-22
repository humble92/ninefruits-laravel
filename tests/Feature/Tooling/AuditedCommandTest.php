<?php

use App\Domain\Security\Actions\SyncPermissionCatalog;
use App\Models\Security\Activity;
use App\Models\Security\Permission;
use App\Models\User;

beforeEach(function () {
    app(SyncPermissionCatalog::class)->handle();
});

it('records the run, options, operating system user and exit code in the business audit', function () {
    $admin = User::factory()->create(['email' => 'ops@example.com']);

    $this->artisan('security:sync-permissions', ['--actor' => 'ops@example.com'])->assertSuccessful();

    $activity = Activity::query()->where('event', 'command.executed')->sole();

    expect($activity->log_name)->toBe('command')
        ->and($activity->causer_id)->toBe($admin->id)
        ->and($activity->properties['command'])->toBe('security:sync-permissions')
        ->and($activity->properties['exit_code'])->toBe(0)
        ->and($activity->properties['os_user'])->not->toBeEmpty()
        ->and($activity->properties['options'])->toHaveKey('actor');
});

it('rejects an unknown --actor', function () {
    $this->artisan('security:sync-permissions', ['--actor' => 'nobody@example.com'])->assertFailed();
});

it('changes nothing in a dry run', function () {
    Permission::query()->create(['name' => 'legacy.removed', 'guard_name' => 'web']);

    $this->artisan('security:sync-permissions', ['--dry-run' => true])
        ->expectsOutputToContain('legacy.removed')
        ->assertSuccessful();

    expect(Permission::query()->where('name', 'legacy.removed')->exists())->toBeTrue()
        ->and(Activity::query()->where('event', 'command.executed')->sole()->properties['dry_run'])->toBeTrue();
});

it('asks before deleting permissions and deletes them with --force', function () {
    Permission::query()->create(['name' => 'legacy.removed', 'guard_name' => 'web']);

    $this->artisan('security:sync-permissions')
        ->expectsConfirmation('Delete 1 permission(s) no longer in the catalog?', 'no')
        ->assertFailed();

    expect(Permission::query()->where('name', 'legacy.removed')->exists())->toBeTrue();

    $this->artisan('security:sync-permissions', ['--force' => true])->assertSuccessful();

    expect(Permission::query()->where('name', 'legacy.removed')->exists())->toBeFalse();
});

it('returns exit code 2 when a check finds problems', function () {
    $path = sys_get_temp_dir().'/naming-'.uniqid();
    mkdir($path);
    file_put_contents("{$path}/2026_01_01_000000_create_scores_backup.php", "<?php Schema::create('scores_backup', fn () => null);");

    $this->artisan('naming:check', ['--path' => [$path]])->assertExitCode(2);

    array_map('unlink', glob("{$path}/*"));
    rmdir($path);
});
