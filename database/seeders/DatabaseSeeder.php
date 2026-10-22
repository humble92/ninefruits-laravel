<?php

namespace Database\Seeders;

use App\Support\Database\DatabaseSessionContext;
use Database\Seeders\Essential\EssentialSeeder;
use Illuminate\Database\Seeder;

/**
 * Runs only the essential seeders. Profiles are applied by setup:install, demo data by
 * setup:demo.
 */
class DatabaseSeeder extends Seeder
{
    public function run(DatabaseSessionContext $context): void
    {
        // migrate:fresh reconnects after db:wipe, so the command context may be gone.
        $context->forSystem('seed');

        $this->call(EssentialSeeder::class);
    }
}
