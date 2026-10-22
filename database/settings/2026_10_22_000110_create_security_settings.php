<?php

use App\Domain\Security\Enums\MfaEnforcement;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('security.mfa_enforcement', MfaEnforcement::Recommended->value);
        $this->migrator->add('security.mfa_required_role_ids', []);
        $this->migrator->add('security.mfa_grace_period_days', 7);
        $this->migrator->add('security.mfa_remember_device_days', 30);
    }
};
