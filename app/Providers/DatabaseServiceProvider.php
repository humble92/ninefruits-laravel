<?php

namespace App\Providers;

use App\Support\Database\DatabaseSessionContext;
use App\Support\Database\PostgresConnection;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class DatabaseServiceProvider extends ServiceProvider
{
    /**
     * Commands that change the schema and therefore run as the schema owner.
     *
     * @var list<string>
     */
    public const array MIGRATOR_COMMANDS = [
        'migrate', 'migrate:fresh', 'migrate:install', 'migrate:refresh', 'migrate:reset',
        'migrate:rollback', 'migrate:status', 'db:wipe', 'schema:dump',
    ];

    /**
     * Previous default connections while a migration command runs.
     *
     * @var list<string>
     */
    private array $previousDefaults = [];

    public function register(): void
    {
        $this->app->singleton(DatabaseSessionContext::class);

        Connection::resolverFor('pgsql', fn ($connection, string $database, string $prefix, array $config) => new PostgresConnection($connection, $database, $prefix, $config));
    }

    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());
        Model::automaticallyEagerLoadRelationships(false);

        $this->useMigratorConnectionForSchemaCommands();
        $this->setSystemContextForQueuedJobs();
        $this->setSystemContextForConsoleCommands();
    }

    private function useMigratorConnectionForSchemaCommands(): void
    {
        Event::listen(CommandStarting::class, function (CommandStarting $event): void {
            if (! in_array($event->command, self::MIGRATOR_COMMANDS, true)) {
                return;
            }

            $this->previousDefaults[] = config('database.default');

            if (! $event->input->hasParameterOption('--database')) {
                config(['database.default' => config('database.migrator_connection')]);
            }
        });

        Event::listen(CommandFinished::class, function (CommandFinished $event): void {
            if (in_array($event->command, self::MIGRATOR_COMMANDS, true) && $this->previousDefaults !== []) {
                $this->app->make(DatabaseSessionContext::class)->reset();
                config(['database.default' => array_pop($this->previousDefaults)]);
            }
        });
    }

    private function setSystemContextForConsoleCommands(): void
    {
        // Registered after the migrator switch, so migration and seed commands get the
        // context on the migrator connection.
        Event::listen(CommandStarting::class, function (CommandStarting $event): void {
            if ($event->command !== '') {
                $this->app->make(DatabaseSessionContext::class)->forSystem('command:'.$event->command);
            }
        });

        Event::listen(CommandFinished::class, function (CommandFinished $event): void {
            if ($event->command !== '') {
                $this->app->make(DatabaseSessionContext::class)->reset();
            }
        });
    }

    private function setSystemContextForQueuedJobs(): void
    {
        Event::listen(JobProcessing::class, function (JobProcessing $event): void {
            $this->app->make(DatabaseSessionContext::class)->forSystem(
                'job:'.$event->job->resolveName(),
                Context::get('user_id'),
                Context::get('request_id'),
            );
        });

        $reset = fn () => $this->app->make(DatabaseSessionContext::class)->reset();

        Event::listen(JobProcessed::class, $reset);
        Event::listen(JobFailed::class, $reset);
    }
}
