<?php

namespace Tests\Support;

use App\Support\Database\DatabaseSessionContext;
use Livewire\ComponentHook;

/**
 * Livewire component tests skip the HTTP middleware, so without this they would run as
 * the test's system baseline and never meet row level security. While a component
 * request is processed the database context is the signed-in user, as in a real request;
 * afterwards the test is back to the baseline for its fixtures (Phase 13, decided 2026-10-23).
 */
class LivewireDatabaseContextHook extends ComponentHook
{
    public function boot(): void
    {
        $this->context()->forUser(auth()->id() === null ? null : (int) auth()->id());
    }

    public function dehydrate(): void
    {
        $this->context()->reset();
    }

    public function exception(): void
    {
        $this->context()->reset();
    }

    private function context(): DatabaseSessionContext
    {
        return app(DatabaseSessionContext::class);
    }
}
