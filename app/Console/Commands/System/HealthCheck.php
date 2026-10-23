<?php

namespace App\Console\Commands\System;

use App\Domain\Security\Enums\Permission;
use App\Models\Security\Permission as PermissionModel;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Throwable;

#[Signature('system:health-check {--json : Machine-readable output}')]
#[Description('Check settings, keys, database roles and RLS, pending migrations, permissions, queue, Redis, Gotenberg, storage and the scheduler; exit 2 on a problem')]
class HealthCheck extends Command
{
    public const string HEARTBEAT_KEY = 'system.scheduler_heartbeat';

    /** @var list<array{check: string, ok: bool, detail: string}> */
    private array $results = [];

    public function handle(): int
    {
        $this->check('app_key', fn () => [filled(config('app.key')), 'APP_KEY '.(filled(config('app.key')) ? 'set' : 'missing')]);
        $this->check('pii_keys', fn () => [filled(config('pii.key')) && filled(config('pii.hash_key')), 'PII_ENCRYPTION_KEY and PII_HASH_KEY '.(filled(config('pii.key')) && filled(config('pii.hash_key')) ? 'set' : 'missing')]);
        $this->check('debug_off', fn () => [! config('app.debug') || app()->environment('local', 'testing'), 'APP_DEBUG='.(config('app.debug') ? 'true' : 'false').' ('.app()->environment().')']);
        $this->check('database', function () {
            $user = DB::selectOne('SELECT current_user AS u')->u;
            $migrator = DB::connection((string) config('database.migrator_connection'))->selectOne('SELECT current_user AS u')->u;
            $reporting = DB::selectOne('SELECT count(*) AS c FROM pg_roles WHERE rolname = ?', ['app_reporting'])->c;

            return [$user !== $migrator && $reporting > 0, "runtime {$user}, migrator {$migrator}, reporting role ".($reporting > 0 ? 'present' : 'missing')];
        });
        $this->check('row_level_security', function () {
            $off = DB::select("SELECT c.relname FROM pg_class c JOIN pg_namespace n ON n.oid = c.relnamespace WHERE n.nspname = 'public' AND c.relkind = 'r' AND c.relname IN ('people', 'student_records', 'enrollments', 'applications') AND NOT c.relrowsecurity");

            return [$off === [], $off === [] ? 'enabled on the personal data tables' : 'disabled on: '.implode(', ', array_column($off, 'relname'))];
        });
        $this->check('migrations', function () {
            $migrator = app('migrator');
            // usingConnection restores the default connection afterwards (setConnection would not).
            $pending = $migrator->usingConnection((string) config('database.migrator_connection'), fn () => count(array_diff(
                array_keys($migrator->getMigrationFiles(database_path('migrations'))), $migrator->getRepository()->getRan(),
            )));

            return [$pending === 0, $pending === 0 ? 'up to date' : "{$pending} pending"];
        });
        $this->check('permissions', function () {
            $missing = array_diff(array_map(fn (Permission $p) => $p->value, Permission::cases()), PermissionModel::query()->pluck('name')->all());

            return [$missing === [], $missing === [] ? count(Permission::cases()).' in sync' : 'not synced: '.implode(', ', array_slice($missing, 0, 5)).' (run security:sync-permissions)'];
        });
        $this->check('redis', function () {
            if (config('cache.default') !== 'redis' && config('queue.default') !== 'redis' && config('session.driver') !== 'redis') {
                return [true, 'not used'];
            }
            Redis::connection()->ping();

            return [true, 'ping ok'];
        });
        $this->check('queue', function () {
            $connection = (string) config('queue.default');

            if ($connection !== 'redis') {
                return [true, "connection {$connection}"];
            }
            $masters = app(MasterSupervisorRepository::class)->all();

            return [count($masters) > 0, count($masters) > 0 ? count($masters).' Horizon supervisor(s) running' : 'Horizon is not running'];
        });
        $this->check('gotenberg', function () {
            $response = Http::timeout(5)->get(rtrim((string) config('services.gotenberg.url'), '/').'/health');

            return [$response->successful(), 'HTTP '.$response->status()];
        });
        $this->check('storage', function () {
            $disk = Storage::disk((string) config('media-library.disk_name'));
            $disk->put('.health-check', (string) now());
            $ok = $disk->exists('.health-check');
            $disk->delete('.health-check');

            return [$ok, 'media disk '.config('media-library.disk_name').($ok ? ' writable' : ' not writable')];
        });
        $this->check('scheduler', function () {
            $last = Cache::get(self::HEARTBEAT_KEY);
            $ok = $last !== null && now()->diffInMinutes($last) <= 5;

            return [$ok || app()->environment('testing'), $last === null ? 'no heartbeat yet (schedule:run not running)' : "last run {$last}"];
        });

        $failed = count(array_filter($this->results, fn (array $r) => ! $r['ok']));

        if ($this->option('json')) {
            $this->line((string) json_encode(['ok' => $failed === 0, 'checks' => $this->results], JSON_PRETTY_PRINT));
        } else {
            foreach ($this->results as $result) {
                $this->components->twoColumnDetail(($result['ok'] ? '<fg=green>OK</> ' : '<fg=red>FAIL</> ').$result['check'], $result['detail']);
            }
            $failed === 0 ? $this->components->info('All checks passed.') : $this->components->error("{$failed} check(s) failed.");
        }

        return $failed === 0 ? self::SUCCESS : 2;
    }

    /**
     * @param  callable(): array{0: bool, 1: string}  $probe
     */
    private function check(string $name, callable $probe): void
    {
        try {
            [$ok, $detail] = $probe();
        } catch (Throwable $e) {
            [$ok, $detail] = [false, mb_substr($e->getMessage(), 0, 160)];
        }

        $this->results[] = ['check' => $name, 'ok' => $ok, 'detail' => $detail];
    }
}
