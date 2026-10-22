<?php

namespace App\Jobs\Security;

use App\Models\Contracts\EncryptsPersonalData;
use App\Support\Pii\PiiCipher;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Re-encrypts personal data that is still encrypted with a previous PII key.
 */
class ReencryptPiiRecords implements ShouldQueue
{
    use Batchable, Queueable;

    /**
     * @param  class-string<Model&EncryptsPersonalData>  $modelClass
     * @param  list<int>  $ids
     */
    public function __construct(public string $modelClass, public array $ids) {}

    public function handle(PiiCipher $cipher): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $model = new $this->modelClass;
        $columns = array_map(fn (string $name) => "{$name}_encrypted", array_keys($model->encryptedPiiAttributes()));

        $this->modelClass::query()->whereKey($this->ids)->lazyById()->each(function (Model $record) use ($cipher, $columns) {
            $changed = false;

            foreach ($columns as $column) {
                $payload = $record->getRawOriginal($column);

                if ($payload !== null && ! $cipher->isEncryptedWithCurrentKey($payload)) {
                    // Assigning the decrypted value encrypts it again with the current key.
                    $record->setAttribute($column, $record->getAttribute($column));
                    $changed = true;
                }
            }

            if ($changed) {
                $record->save();
            }
        });
    }
}
