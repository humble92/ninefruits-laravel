<?php

namespace App\Console\Commands\Setup;

use App\Console\Commands\AuditedCommand;
use App\Support\Setup\DemoOptions;
use Database\Seeders\Demo\DemoAccountSeeder;
use Database\Seeders\Demo\DemoAdmissionSeeder;
use Database\Seeders\Demo\DemoPeopleSeeder;
use Database\Seeders\Demo\DemoSeeder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;

use function Laravel\Prompts\confirm;

#[Signature('setup:demo
    {--size=small : tiny (about 20 students), small (about 100), medium (about 2,000) or large (about 20,000)}
    {--seed= : Faker seed for reproducible data}
    {--force : Do not ask for confirmation (still refused in production)}')]
#[Description('Create a fictional demo university from factories. Never runs in production.')]
class Demo extends AuditedCommand
{
    public function handle(): int
    {
        if ($this->laravel->isProduction()) {
            $this->components->error('Demo data cannot be created in production.');

            return self::FAILURE;
        }

        $size = (string) $this->option('size');

        if (! array_key_exists($size, (array) config('setup.demo.sizes'))) {
            $this->components->error('Size must be one of: '.implode(', ', array_keys((array) config('setup.demo.sizes'))).'.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! confirm("Create {$size} demo data in the [".config('database.connections.'.config('database.default').'.database').'] database?')) {
            return self::FAILURE;
        }

        $seed = $this->option('seed') === null ? null : (int) $this->option('seed');
        $this->laravel->instance(DemoOptions::class, new DemoOptions($size, $seed));

        $this->call('db:seed', ['--class' => DemoSeeder::class, '--force' => true]);

        $this->recordResult(['size' => $size, 'seed' => $seed]);

        $this->components->info('Demo accounts (password: '.config('setup.demo.password').')');

        foreach ([...DemoAccountSeeder::emails(), ...DemoPeopleSeeder::emails(), ...DemoAdmissionSeeder::emails()] as $email) {
            $this->line("  {$email}");
        }

        return self::SUCCESS;
    }
}
