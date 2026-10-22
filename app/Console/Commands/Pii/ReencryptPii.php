<?php

namespace App\Console\Commands\Pii;

use App\Console\Commands\AuditedCommand;
use App\Jobs\Security\ReencryptPiiRecords;
use App\Models\Contracts\EncryptsPersonalData;
use App\Support\Pii\PiiCipher;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Bus;

use function Laravel\Prompts\confirm;

#[Signature('pii:reencrypt {model : Model class implementing EncryptsPersonalData} {--chunk=500} {--dry-run : Count records still encrypted with a previous key} {--force : Do not ask for confirmation}')]
#[Description('Re-encrypt personal data with the current PII key after a key rotation (queued batch)')]
class ReencryptPii extends AuditedCommand
{
    public function handle(PiiCipher $cipher): int
    {
        $modelClass = (string) $this->argument('model');

        if (! is_subclass_of($modelClass, Model::class) || ! is_subclass_of($modelClass, EncryptsPersonalData::class)) {
            $this->components->error("[{$modelClass}] is not a model implementing EncryptsPersonalData.");

            return self::FAILURE;
        }

        if ($this->isDryRun()) {
            $columns = array_map(fn (string $name) => "{$name}_encrypted", array_keys((new $modelClass)->encryptedPiiAttributes()));
            $outdated = 0;

            $modelClass::query()->lazyById((int) $this->option('chunk'))->each(function (Model $record) use ($columns, $cipher, &$outdated) {
                foreach ($columns as $column) {
                    $payload = $record->getRawOriginal($column);

                    if ($payload !== null && ! $cipher->isEncryptedWithCurrentKey($payload)) {
                        $outdated++;

                        return;
                    }
                }
            });

            $this->recordResult(['outdated_records' => $outdated]);
            $this->components->info("Dry run: {$outdated} record(s) still use a previous key.");

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! confirm("Re-encrypt every {$modelClass} record with the current PII key?")) {
            return self::FAILURE;
        }

        $jobs = [];

        $modelClass::query()->select((new $modelClass)->getKeyName())->chunkById((int) $this->option('chunk'), function ($records) use (&$jobs, $modelClass) {
            $jobs[] = new ReencryptPiiRecords($modelClass, $records->modelKeys());
        });

        $batch = Bus::batch($jobs)->name("pii:reencrypt {$modelClass}")->allowFailures()->dispatch();

        $this->recordResult(['batch_id' => $batch->id, 'jobs' => count($jobs)]);
        $this->components->info(sprintf('Dispatched batch %s with %d jobs.', $batch->id, count($jobs)));

        return self::SUCCESS;
    }
}
