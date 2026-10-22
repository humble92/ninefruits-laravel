<?php

namespace Database\Seeders\Demo;

use App\Support\Database\DatabaseSessionContext;
use App\Support\Setup\DemoOptions;
use Illuminate\Database\Seeder;

/**
 * Fictional university for development, training and demonstrations. Built from factories
 * only. Each Phase appends its demo seeder in dependency order
 * (docs/porting-plan.md section 13.4).
 */
class DemoSeeder extends Seeder
{
    /** @var list<class-string<Seeder>> */
    public const array SEEDERS = [
        DemoAccountSeeder::class,
    ];

    public function run(DatabaseSessionContext $context): void
    {
        DemoOptions::ensureNotProduction();

        $options = app()->bound(DemoOptions::class) ? app(DemoOptions::class) : new DemoOptions;

        if ($options->seed !== null) {
            fake()->seed($options->seed);
            mt_srand($options->seed);
        }

        $context->forSystem('seed:demo');

        $this->call(self::SEEDERS);
    }
}
