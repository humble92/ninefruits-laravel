<?php

namespace App\Console\Commands;

use App\Domain\Security\Services\AuditLogger;
use App\Models\User;
use App\Support\Database\DatabaseSessionContext;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Base class for commands that change data (porting/porting-plan.md section 13.2).
 *
 * - Records the run in the business audit (log "command"): command, options, operating
 *   system user, optional --actor account, exit code and the result set with recordResult().
 * - Adds --actor=<email> so an administrator can be named as the person responsible.
 * - Gives the run an id (request_id) shared by its audit entry, row changes and jobs.
 * - Commands that support --dry-run declare it in their signature and call isDryRun().
 *
 * Read-only check commands extend Illuminate\Console\Command directly and are not audited.
 */
abstract class AuditedCommand extends Command
{
    /** Exit code for check commands that found problems. */
    public const int PROBLEMS_FOUND = 2;

    /** @var array<string, mixed> */
    private array $result = [];

    public function __construct()
    {
        parent::__construct();

        $this->getDefinition()->addOption(new InputOption(
            'actor', null, InputOption::VALUE_REQUIRED, 'Email of the administrator responsible for this run (recorded in the audit)',
        ));
    }

    /**
     * @param  array<string, mixed>  $result
     */
    protected function recordResult(array $result): void
    {
        $this->result = array_merge($this->result, $result);
    }

    protected function isDryRun(): bool
    {
        return $this->getDefinition()->hasOption('dry-run') && (bool) $this->option('dry-run');
    }

    protected function actor(): ?User
    {
        $email = $this->option('actor');

        return is_string($email) && $email !== '' ? User::query()->where('email', $email)->first() : null;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Run id in the request_id slot: the audit entry below, the row changes and the jobs
        // of this run share it. Also set when called from code or tests, where the
        // CommandStarting event (DatabaseServiceProvider) does not fire.
        if (! Context::has('request_id')) {
            Context::add('request_id', 'cmd-'.Str::uuid());
        }

        app(DatabaseSessionContext::class)->forSystem('command:'.$this->getName(), requestId: Context::get('request_id'));

        $actorEmail = $input->getOption('actor');

        if (is_string($actorEmail) && $actorEmail !== '' && $this->actor() === null) {
            $this->components->error("No user with the email [{$actorEmail}] for --actor.");

            return self::FAILURE;
        }

        try {
            $status = (int) parent::execute($input, $output);
        } catch (Throwable $exception) {
            // The audit write itself may fail (e.g. an aborted transaction): the original error must surface.
            try {
                $this->audit(self::FAILURE, ['exception' => $exception::class, 'message' => $exception->getMessage()]);
            } catch (Throwable) {
                // Reported through the rethrown exception below.
            }

            throw $exception;
        }

        $this->audit($status);

        return $status;
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function audit(int $status, array $extra = []): void
    {
        // Before the first migration (e.g. setup:install refusing to start) there is nowhere to write.
        if (! Schema::hasTable('audit.activity_log')) {
            $this->components->warn('The audit tables do not exist yet; this run was not recorded.');

            return;
        }

        $options = array_filter(
            $this->options(),
            fn (mixed $value, string $name) => $value !== null && $value !== false && $value !== []
                && ! in_array($name, ['help', 'quiet', 'verbose', 'version', 'ansi', 'no-interaction', 'env'], true),
            ARRAY_FILTER_USE_BOTH,
        );

        app(AuditLogger::class)->record(
            'command.executed',
            null,
            $this->getDefinition()->hasOption('reason') && is_string($this->option('reason')) ? $this->option('reason') : null,
            [
                'command' => $this->getName(),
                'arguments' => array_filter($this->arguments(), fn (mixed $value, string $name) => $name !== 'command' && $value !== null, ARRAY_FILTER_USE_BOTH),
                'options' => $options,
                'os_user' => $this->operatingSystemUser(),
                'exit_code' => $status,
                'dry_run' => $this->isDryRun(),
                'result' => [...$this->result, ...$extra],
            ],
            $this->actor(),
            'command',
        );
    }

    private function operatingSystemUser(): string
    {
        if (function_exists('posix_geteuid') && function_exists('posix_getpwuid')) {
            $entry = posix_getpwuid(posix_geteuid());

            if (is_array($entry)) {
                return $entry['name'];
            }
        }

        return (string) (getenv('USER') ?: get_current_user());
    }
}
