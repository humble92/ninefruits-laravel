<?php

namespace App\Models\Security;

use App\Domain\Security\Enums\SecurityEventType;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property CarbonImmutable $occurred_at
 * @property SecurityEventType $type
 * @property int|null $user_id
 * @property string|null $identifier
 * @property string|null $subject_type
 * @property int|null $subject_id
 * @property array<string, mixed>|null $properties
 * @property string|null $request_id
 * @property string|null $ip_address
 * @property string|null $user_agent
 */
class SecurityEvent extends Model
{
    protected $table = 'audit.security_events';

    public $timestamps = false;

    protected $fillable = ['occurred_at', 'type', 'user_id', 'identifier', 'subject_type', 'subject_id', 'properties', 'request_id', 'ip_address', 'user_agent'];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'immutable_datetime',
            'type' => SecurityEventType::class,
            'properties' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
