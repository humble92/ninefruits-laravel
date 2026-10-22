<?php

namespace App\Domain\Security\Services;

use App\Models\Security\Activity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Business audit: who did what, to which record, and why. Request context (request id,
 * IP address, user agent) is attached by the callback registered in AppServiceProvider.
 */
class AuditLogger
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public function record(
        string $event,
        ?Model $subject,
        ?string $reason = null,
        array $properties = [],
        ?Model $causer = null,
        string $log = 'default',
    ): Activity {
        $logger = activity($log)
            ->event($event)
            ->causedBy($causer ?? Auth::user())
            ->withProperties($properties)
            ->tap(function (Activity $activity) use ($reason): void {
                $activity->reason = $reason;
            });

        if ($subject !== null) {
            $logger->performedOn($subject);
        }

        /** @var Activity */
        return $logger->log($event);
    }
}
