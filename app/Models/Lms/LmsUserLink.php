<?php

namespace App\Models\Lms;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A user's account on a platform.
 *
 * @property int $id
 * @property int $lms_platform_id
 * @property int $user_id
 * @property string $external_user_id
 * @property CarbonImmutable|null $last_synced_at
 * @property-read LmsPlatform $platform
 * @property-read User $user
 */
class LmsUserLink extends Model
{
    protected $fillable = ['lms_platform_id', 'user_id', 'external_user_id', 'last_synced_at'];

    protected $attributes = ['last_synced_at' => null];

    protected function casts(): array
    {
        return ['last_synced_at' => 'immutable_datetime'];
    }

    /**
     * @return BelongsTo<LmsPlatform, $this>
     */
    public function platform(): BelongsTo
    {
        return $this->belongsTo(LmsPlatform::class, 'lms_platform_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
