<?php

namespace App\Models\Concerns;

use App\Casts\EncryptedPii;
use App\Support\Pii\PiiHasher;
use App\Support\Pii\PiiType;
use Illuminate\Database\Eloquent\Builder;

/**
 * Implementation of App\Models\Contracts\EncryptsPersonalData.
 *
 * Encrypted personal data columns with a search hash.
 *
 * For each entry in encryptedPiiAttributes(), e.g. ['national_id' => PiiType::NationalId],
 * the table has national_id_encrypted (text) and national_id_hash (char 64, indexed).
 * Read and write the plaintext through the *_encrypted attribute; the hash is kept in sync.
 *
 * Screens show maskedPii() by default. Showing the value requires the *.view_sensitive
 * permission and goes through App\Domain\Security\Actions\RevealSensitiveValue (logged).
 */
trait HasEncryptedPii
{
    public function initializeHasEncryptedPii(): void
    {
        foreach (array_keys($this->encryptedPiiAttributes()) as $name) {
            $this->mergeCasts(["{$name}_encrypted" => EncryptedPii::class]);
        }
    }

    public static function bootHasEncryptedPii(): void
    {
        static::saving(function (self $model): void {
            foreach ($model->encryptedPiiAttributes() as $name => $type) {
                if ($model->isDirty("{$name}_encrypted")) {
                    $value = $model->getAttribute("{$name}_encrypted");
                    $model->setAttribute("{$name}_hash", $value === null ? null : app(PiiHasher::class)->hash($value, $type));
                }
            }
        });
    }

    /**
     * Exact match on an encrypted attribute through its hash.
     *
     * @param  Builder<static>  $query
     */
    public function scopeWherePii(Builder $query, string $name, string $value): void
    {
        $type = $this->encryptedPiiAttributes()[$name];

        $query->where("{$name}_hash", app(PiiHasher::class)->hash($value, $type));
    }

    public function maskedPii(string $name): ?string
    {
        $value = $this->getAttribute("{$name}_encrypted");

        return $value === null ? null : $this->encryptedPiiAttributes()[$name]->mask($value);
    }
}
