<?php

namespace App\Console\Commands\Pii;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('pii:generate-key')]
#[Description('Print new keys for PII_ENCRYPTION_KEY and PII_HASH_KEY')]
class GenerateKey extends Command
{
    public function handle(): int
    {
        $this->line('PII_ENCRYPTION_KEY=base64:'.base64_encode(random_bytes(32)));
        $this->line('PII_HASH_KEY=base64:'.base64_encode(random_bytes(32)));
        $this->newLine();
        $this->components->warn('Changing PII_HASH_KEY invalidates every *_hash search index. Rotate only the encryption key: move the old key to PII_PREVIOUS_KEYS, then run pii:reencrypt.');

        return self::SUCCESS;
    }
}
