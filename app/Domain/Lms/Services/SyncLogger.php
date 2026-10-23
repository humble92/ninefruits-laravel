<?php

namespace App\Domain\Lms\Services;

use App\Domain\Lms\Enums\SyncOperation;
use App\Domain\Lms\Enums\SyncStatus;
use App\Models\Lms\LmsPlatform;
use App\Models\Lms\LmsSyncLog;
use Carbon\CarbonImmutable;
use Throwable;

/**
 * Wraps one synchronization step in a log row: queued → succeeded/failed/skipped.
 */
class SyncLogger
{
    /**
     * @param  array{section_id?: int|null, enrollment_id?: int|null, user_id?: int|null, payload?: array<string, mixed>|null}  $context
     * @param  callable(): (array<string, mixed>|string|null)  $step  returns a payload or a message
     */
    public function run(LmsPlatform $platform, SyncOperation $operation, array $context, callable $step, bool $dryRun = false): LmsSyncLog
    {
        $log = LmsSyncLog::query()->create([
            'lms_platform_id' => $platform->id, 'section_id' => $context['section_id'] ?? null, 'enrollment_id' => $context['enrollment_id'] ?? null, 'user_id' => $context['user_id'] ?? null,
            'operation' => $operation, 'status' => SyncStatus::Queued, 'payload' => $context['payload'] ?? null, 'started_at' => CarbonImmutable::now(),
        ]);

        if ($dryRun) {
            return $this->finish($log, SyncStatus::Skipped, 'dry run');
        }

        try {
            $result = $step();

            return $this->finish($log, SyncStatus::Succeeded, is_string($result) ? $result : null, is_array($result) ? $result : null);
        } catch (Throwable $e) {
            $this->finish($log, SyncStatus::Failed, mb_substr($e->getMessage(), 0, 1000));

            throw $e;
        }
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    public function finish(LmsSyncLog $log, SyncStatus $status, ?string $message = null, ?array $payload = null): LmsSyncLog
    {
        $log->forceFill(['status' => $status, 'message' => $message, 'payload' => $payload ?? $log->payload, 'finished_at' => CarbonImmutable::now()])->save();

        return $log;
    }
}
