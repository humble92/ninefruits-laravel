<?php

use Database\Seeders\Profiles\KoreanUniversityProfileSeeder;
use Database\Seeders\Profiles\YustLegacyRegistrationProfileSeeder;

/*
| Installation (setup:install) and demo data (setup:demo).
| docs/porting-plan.md section 13.4.
*/

return [
    // Profile seeders offered by setup:install. Each implements App\Support\Setup\SetupProfile.
    'profiles' => [
        KoreanUniversityProfileSeeder::class,
        YustLegacyRegistrationProfileSeeder::class,
    ],

    'demo' => [
        // Students per size. Other volumes scale from this.
        'sizes' => [
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
