<?php

namespace App\Support\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use PDO;

/**
 * Sets PostgreSQL session settings read by row level security policies and audit triggers
 * (util.current_user_id(), util.current_actor(), util.i18n()).
 *
 * Settings are session level, so they must be reset when a request or job ends
 * because Octane workers and queue workers reuse connections.
 */
class DatabaseSessionContext
{
    private const array KEYS = ['app.user_id', 'app.actor', 'app.request_id', 'app.locale', 'app.fallback_locale'];

    /** @var array<int, array<string, string>> Settings not yet sent, per connection object */
    private array $pending = [];

    /** @var array<int, true> Connections with the beforeExecuting hook */
    private array $hooked = [];

    public function __construct(private readonly DatabaseManager $database) {}

    public function forUser(?int $userId, ?string $requestId = null): void
    {
        $this->apply([
            'app.user_id' => $userId === null ? '' : (string) $userId,
            'app.actor' => $userId === null ? '' : "user:{$userId}",
            'app.request_id' => (string) $requestId,
        ]);
    }

    /**
     * Queued jobs, scheduled tasks and console commands. RLS policies grant system actors full access.
     */
    public function forSystem(string $name, ?int $initiatedByUserId = null, ?string $requestId = null): void
    {
        $this->apply([
            'app.user_id' => $initiatedByUserId === null ? '' : (string) $initiatedByUserId,
            'app.actor' => "system:{$name}",
            'app.request_id' => (string) $requestId,
        ]);
    }

    public function setLocale(string $locale, string $fallbackLocale): void
    {
        $this->apply(['app.locale' => $locale, 'app.fallback_locale' => $fallbackLocale]);
    }

    public function reset(): void
    {
        $this->apply(array_fill_keys(self::KEYS, ''));
    }

    /**
     * @return array<string, string|null>
     */
    public function current(): array
    {
        $placeholders = implode(', ', array_map(
            fn (string $key) => "current_setting(?, true) AS \"{$key}\"",
            self::KEYS,
        ));

        return (array) $this->database->connection()->selectOne("SELECT {$placeholders}", self::KEYS);
    }

    /**
     * @param  array<string, string>  $settings
     */
    private function apply(array $settings): void
    {
        $connection = $this->database->connection();

        if ($connection->getDriverName() !== 'pgsql') {
            return;
        }

        $key = spl_object_id($connection);
        $this->pending[$key] = array_merge($this->pending[$key] ?? [], $settings);

        if ($connection->getRawPdo() instanceof PDO) {
            $this->flush($connection);

            return;
        }

        // Not connected yet: send the settings right before the first query, so commands
        // that never use the database (key:generate, package:discover) need no database.
        if (! isset($this->hooked[$key])) {
            $this->hooked[$key] = true;
            $connection->beforeExecuting(function () use ($connection): void {
                $this->flush($connection);
            });
        }
    }

    private function flush(Connection $connection): void
    {
        $key = spl_object_id($connection);
        $settings = $this->pending[$key] ?? [];

        if ($settings === []) {
            return;
        }

        unset($this->pending[$key]);

        $bindings = [];

        foreach ($settings as $name => $value) {
            $bindings[] = $name;
            $bindings[] = $value;
        }

        // Directly on PDO so that it does not trigger beforeExecuting again.
        $connection->getPdo()
            ->prepare('SELECT '.implode(', ', array_fill(0, count($settings), 'set_config(?, ?, false)')))
            ->execute($bindings);
    }
}
