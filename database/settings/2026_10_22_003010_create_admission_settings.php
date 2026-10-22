<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('admissions.applicant_registration_enabled', true);
        $this->migrator->add('admissions.draft_expiry_days', 90);
        $this->migrator->add('admissions.max_document_size_mb', 10);
    }
};
