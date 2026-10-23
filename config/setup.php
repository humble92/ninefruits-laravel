<?php

use Database\Seeders\Profiles\KoreanUniversityProfileSeeder;
use Database\Seeders\Profiles\YustLegacyProfileSeeder;

/*
| Installation (setup:install) and demo data (setup:demo).
| porting/porting-plan.md section 13.4.
*/

return [
    // Profile seeders offered by setup:install. Each implements App\Support\Setup\SetupProfile.
    'profiles' => [
        KoreanUniversityProfileSeeder::class,
        YustLegacyProfileSeeder::class,
    ],

    'demo' => [
        // Students per size. Other volumes scale from this.
        'sizes' => [
            // For tests and quick checks: every kind of demo data, as few rows as possible.
            'tiny' => 20,
            'small' => 100,
            'medium' => 2000,
            'large' => 20000,
        ],

        // Demo accounts use this reserved domain only.
        'email_domain' => 'demo.test',

        // Password of the fixed demo accounts printed by setup:demo.
        'password' => 'demo-password',
    ],
];
