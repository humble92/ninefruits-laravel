<?php

namespace App\Models\Security;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * External sign-in identity of an account, e.g. the school's OIDC account.
 *
 * @property int $id
 * @property int $user_id
 * @property string $provider
 * @property string $subject
 * @property string|null $email
 * @property CarbonImmutable $linked_at
 * @property CarbonImmutable|null $last_used_at
 * @property-read User $user
 */
class UserIdentity extends Model
{
    protected $fillable = ['user_id', 'provider', 'subject', 'email', 'linked_at', 'last_used_at'];

    protected $attributes = ['email' => null, 'last_used_at' => null];

    protected function casts(): array
    {
        return ['linked_at' => 'immutable_datetime', 'last_used_at' => 'immutable_datetime'];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
