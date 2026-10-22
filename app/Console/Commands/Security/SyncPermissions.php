<?php

namespace App\Console\Commands\Security;

use App\Console\Commands\AuditedCommand;
use App\Domain\Security\Actions\SyncPermissionCatalog;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;

use function Laravel\Prompts\confirm;

#[Signature('security:sync-permissions {--dry-run : Show the changes without applying them} {--force : Delete permissions removed from the catalog without asking}')]
#[Description('Synchronize the permissions table with the permission catalog in code')]
class SyncPermissions extends AuditedCommand
{
    public function handle(SyncPermissionCatalog $sync): int
    {
        $plan = $sync->plan();

        foreach ($plan['created'] as $name) {
            $this->line("  <fg=green>create</> {$name}");
        }

        foreach ($plan['deleted'] as $name) {
            $this->line("  <fg=red>delete</> {$name} (removed from every role)");
        }

        $this->recordResult(['created' => $plan['created'], 'deleted' => $plan['deleted']]);

        if ($this->isDryRun()) {
            $this->components->info(sprintf('Dry run: %d to create, %d to delete.', count($plan['created']), count($plan['deleted'])));

            return self::SUCCESS;
        }

        if ($plan['deleted'] !== [] && ! $this->option('force')
            && ! confirm(sprintf('Delete %d permission(s) no longer in the catalog?', count($plan['deleted'])), default: false)) {
            $this->components->warn('Cancelled. Nothing was changed.');

            return self::FAILURE;
        }

        $result = $sync->handle();

        $this->components->info(sprintf('Permissions created: %d, deleted: %d', count($result['created']), count($result['deleted'])));

        return self::SUCCESS;
    }
}
