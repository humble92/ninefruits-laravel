<?php

namespace Tests;

use App\Support\Database\DatabaseSessionContext;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class DatabaseTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        // Fixtures are created as a system actor so row level security does not block
        // factories. Requests still run as the signed-in user and return to this baseline.
        // Tests of the database policies clear it with useBaseline([]).
        app(DatabaseSessionContext::class)->useBaseline(['app.actor' => 'system:test']);
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
