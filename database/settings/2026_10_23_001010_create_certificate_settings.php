<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Certificate options (porting/decisions/0013-certificates.md).
 */
return new class extends SettingsMigration
{
    private const array DEFAULTS = [
        // Tokens: {TYPE} type code, {YYYY}/{YY} issue year, {SEQ:n} sequence per prefix.
        'certificates.serial_pattern' => '{TYPE}-{YYYY}-{SEQ:6}',
        // Days the verification page confirms an issue; 0 for no limit.
        'certificates.verification_valid_days' => 0,
        'certificates.signer' => [],
    ];

    public function up(): void
    {
        foreach (self::DEFAULTS as $name => $value) {
            $this->migrator->add($name, $value);
        }
    }

    public function down(): void
    {
        foreach (array_keys(self::DEFAULTS) as $name) {
            $this->migrator->delete($name);
        }
    }
};
