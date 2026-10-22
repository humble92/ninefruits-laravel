<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('authentication.student_number_login', 'disabled');
        $this->migrator->add('authentication.password_login_for_sso_users', true);
        $this->migrator->add('authentication.sso_link_mode', 'manual');
        $this->migrator->add('authentication.sso_providers', []);
        $this->migrator->add('authentication.lockout_threshold', 10);
        $this->migrator->add('authentication.lockout_minutes', 15);

        $this->migrator->add('student_records.student_number_pattern', '{YYYY}{SEQ:5}');
        $this->migrator->add('student_records.invitation_valid_days', 14);
    }
};
