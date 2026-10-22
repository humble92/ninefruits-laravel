<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('student_records.max_second_majors', 1);
        $this->migrator->add('student_records.max_minors', 1);
    }

    public function down(): void
    {
        $this->migrator->delete('student_records.max_second_majors');
        $this->migrator->delete('student_records.max_minors');
    }
};
