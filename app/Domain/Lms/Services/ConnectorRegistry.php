<?php

namespace App\Domain\Lms\Services;

use App\Domain\Lms\Connectors\NullConnector;
use App\Domain\Lms\Contracts\LmsConnector;
use App\Domain\Lms\Enums\LmsDriver;
use App\Models\Lms\LmsPlatform;
use Illuminate\Contracts\Container\Container;
use RuntimeException;

/**
 * Driver => connector. Real drivers are added here when they arrive
 * (docs/lms-integration-guide.md); a platform on a driver without a connector fails
 * loudly at sync time instead of silently doing nothing.
 */
class ConnectorRegistry
{
    /** @var array<string, class-string<LmsConnector>> */
    public const array DRIVERS = [
        'null' => NullConnector::class,
    ];

    public function __construct(private readonly Container $container) {}

    public function for(LmsPlatform $platform): LmsConnector
    {
        $class = self::DRIVERS[$platform->driver->value] ?? throw new RuntimeException("No connector for LMS driver [{$platform->driver->value}].");

        return $this->container->make($class);
    }

    public static function isImplemented(LmsDriver $driver): bool
    {
        return array_key_exists($driver->value, self::DRIVERS);
    }
}
