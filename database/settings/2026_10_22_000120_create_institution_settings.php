<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('institution.name', ['en' => config('app.name')]);
        $this->migrator->add('institution.supported_locales', ['en', 'ko', 'zh_CN']);
        $this->migrator->add('institution.default_locale', 'en');
        $this->migrator->add('institution.timezone', config('app.institution_timezone'));
    }
};
