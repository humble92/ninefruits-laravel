<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class DatabaseTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    /**
     * Schema changes run as the schema owner; tests themselves use the runtime role.
     *
     * @return array<string, mixed>
     */
    protected function migrateFreshUsing()
    {
        return array_merge(
            [
                '--drop-views' => $this->shouldDropViews(),
                '--drop-types' => $this->shouldDropTypes(),
                '--database' => config('database.migrator_connection'),
            ],
            ($seeder = $this->seeder()) ? ['--seeder' => $seeder] : ['--seed' => $this->shouldSeed()],
        );
    }
}
