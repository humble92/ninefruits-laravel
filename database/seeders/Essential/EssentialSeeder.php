<?php

namespace Database\Seeders\Essential;

use Illuminate\Database\Seeder;

/**
 * Data the system needs to work. Runs in every environment, on every deployment, and must
 * be idempotent: rows are found by code or name, and values administrators changed are kept
 * (docs/porting-plan.md section 13.4). Add new essential seeders here in dependency order.
 */
class EssentialSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleTemplateSeeder::class,
            CountrySeeder::class,
            LanguageSeeder::class,
            LookupTypeSeeder::class,
            OrgUnitTypeSeeder::class,
            StatusChangeTypeSeeder::class,
        ]);
    }
}
