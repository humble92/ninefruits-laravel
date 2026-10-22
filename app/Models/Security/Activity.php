<?php

namespace App\Models\Security;

use Spatie\Activitylog\Models\Activity as SpatieActivity;

/**
 * Business audit record (who did what, why, with request context).
 *
 * @property string|null $reason
 * @property string|null $request_id
 * @property string|null $ip_address
 * @property string|null $user_agent
 */
class Activity extends SpatieActivity
{
    protected $table = 'audit.activity_log';
}
