<?php

use App\Console\Commands\System\HealthCheck;
use App\Domain\Security\Actions\SyncPermissionCatalog;
use App\Models\Person\Person;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

function migrator(): Connection
{
    return DB::connection((string) config('database.migrator_connection'));
}

/**
 * Rows the commands must see are written on the schema owner's connection, outside the
 * test transaction (seal and prune run there too), and removed afterwards.
 */
function auditRow(string $event, ?string $createdAt = null): int
{
    return (int) migrator()->table('audit.activity_log')->insertGetId([
        'log_name' => 'test', 'description' => $event, 'event' => $event, 'reason' => 'original', 'created_at' => $createdAt ?? now(), 'updated_at' => now(),
    ]);
}

function maintenance(callable $callback): void
{
    migrator()->transaction(function () use ($callback) {
        migrator()->statement("SELECT set_config('app.audit_maintenance', 'on', true)");
        $callback();
    });
}

afterEach(fn () => maintenance(fn () => migrator()->table('audit.activity_log')->where('log_name', 'test')->delete()));

it('hashes audit rows at insert, seals the chain and detects tampering', function () {
    $first = auditRow('test.first');
    $second = auditRow('test.second');

    expect(migrator()->table('audit.activity_log')->where('log_name', 'test')->whereNull('row_hash')->count())->toBe(0);

    $this->artisan('audit:seal', ['--table' => ['activity_log']])->expectsOutputToContain('row(s) sealed')->assertSuccessful();
    expect(migrator()->table('audit.activity_log')->where('id', $first)->value('chain_hash'))->toHaveLength(64);
    $this->artisan('audit:verify-chain')->expectsOutputToContain('Audit chain intact.')->assertSuccessful();

    // The runtime role cannot change audit rows; the owner in maintenance mode can, and the chain shows it.
    expect(databaseError(fn () => DB::table('audit.activity_log')->where('id', $first)->update(['reason' => 'edited'])))->not->toBeNull();
    maintenance(fn () => migrator()->table('audit.activity_log')->where('id', $first)->update(['reason' => 'edited']));
    $this->artisan('audit:verify-chain', ['--table' => ['activity_log']])->expectsOutputToContain('1 row mismatch(es)')->assertExitCode(2);

    maintenance(fn () => migrator()->table('audit.activity_log')->where('id', $second)->delete());
    $this->artisan('audit:verify-chain', ['--table' => ['activity_log']])->expectsOutputToContain('chain mismatch(es)')->assertExitCode(2);
});

it('prunes sealed audit rows past the retention date', function () {
    $old = auditRow('test.old', now()->subYears(3)->toDateTimeString());
    auditRow('test.new');

    $this->artisan('audit:prune', ['--table' => ['activity_log']])->assertFailed();
    $this->artisan('audit:prune', ['--before' => now()->subYear()->toDateString(), '--table' => ['activity_log'], '--dry-run' => true])
        ->expectsOutputToContain('1 unsealed row(s) kept')->assertSuccessful();
    $this->artisan('audit:seal', ['--table' => ['activity_log']])->assertSuccessful();
    $this->artisan('audit:prune', ['--before' => now()->subYear()->toDateString(), '--table' => ['activity_log']])
        ->expectsOutputToContain('deleted 1 row(s)')->assertSuccessful();
    expect(migrator()->table('audit.activity_log')->where('id', $old)->exists())->toBeFalse()
        ->and(migrator()->table('audit.activity_log')->where('event', 'test.new')->exists())->toBeTrue();
    $this->artisan('audit:verify-chain', ['--table' => ['activity_log']])->assertSuccessful();
});

it('removes orphan media rows and directories', function () {
    Storage::fake(config('media-library.disk_name'));
    $person = Person::factory()->create();
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
    $person->addMediaFromString($png)->usingFileName('photo.png')->toMediaCollection(Person::PHOTO_COLLECTION);
    $media = $person->getFirstMedia(Person::PHOTO_COLLECTION);
    Storage::disk(config('media-library.disk_name'))->put('999999/ghost.png', 'y');
    DB::table('media')->where('id', $media->id)->update(['model_id' => 999999999]);

    $this->artisan('media:prune-orphans', ['--dry-run' => true])->expectsOutputToContain('Dry run: 1 orphan row(s), 1 orphan director(ies).')->assertSuccessful();
    expect(DB::table('media')->count())->toBe(1);
    $this->artisan('media:prune-orphans')->expectsOutputToContain('1 orphan row(s), 1 orphan director(ies).')->assertSuccessful();
    expect(DB::table('media')->count())->toBe(0)->and(Storage::disk(config('media-library.disk_name'))->exists('999999/ghost.png'))->toBeFalse();
});

it('reports the system health', function () {
    app(SyncPermissionCatalog::class)->handle();
    Http::fake(['*/health' => Http::response('', 200)]);
    Cache::put(HealthCheck::HEARTBEAT_KEY, now()->toDateTimeString(), 600);

    Artisan::call('system:health-check', ['--json' => true]);
    $report = json_decode(Artisan::output(), true);
    expect(collect($report['checks'])->where('ok', false)->pluck('detail', 'check')->all())->toBe([])
        ->and($report['ok'])->toBeTrue()->and($report['checks'])->toHaveCount(12);

    // A missing application key fails the check.
    config(['app.key' => '']);
    $this->artisan('system:health-check')->expectsOutputToContain('app_key')->assertExitCode(2);
});
