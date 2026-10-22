<?php

namespace App\Support\Database;

use Illuminate\Database\DatabaseManager;

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

        $calls = implode(', ', array_fill(0, count($settings), 'set_config(?, ?, false)'));
        $bindings = [];

        foreach ($settings as $key => $value) {
            $bindings[] = $key;
            $bindings[] = $value;
        }

        // @phpstan-ignore ninefruits.dynamicRawSql (only placeholders are interpolated; keys and values are bound)
        $connection->select("SELECT {$calls}", $bindings);
    }
}
