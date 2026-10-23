<?php

/*
| Personal data encryption (porting/porting-plan.md section 11.2). Uses keys separate from
| APP_KEY so that application key rotation and personal data key rotation are independent.
*/

return [
    'cipher' => 'aes-256-gcm',

    // base64:... 32 byte key. Generate with: php artisan pii:generate-key
    'key' => env('PII_ENCRYPTION_KEY'),

    // Comma separated previous keys, still accepted for decryption during rotation.
    'previous_keys' => array_values(array_filter(explode(',', (string) env('PII_PREVIOUS_KEYS', '')))),

    // Key for the HMAC search index (exact match lookups on encrypted values).
    'hash_key' => env('PII_HASH_KEY'),
];
