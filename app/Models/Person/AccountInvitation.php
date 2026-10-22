<?php

namespace App\Models\Person;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Invitation for a person without an account (e.g. imported students). Only a hash of the
 * token is stored.
 *
 * @property int $id
 * @property int $person_id
 * @property string $email
 * @property string $token_hash
 * @property CarbonImmutable $expires_at
 * @property CarbonImmutable|null $accepted_at
 * @property int|null $invited_by_id
 * @property-read Person $person
 */
class AccountInvitation extends Model
{
    protected $fillable = ['person_id', 'email', 'token_hash', 'expires_at', 'invited_by_id'];

    protected $attributes = ['accepted_at' => null, 'invited_by_id' => null];

    protected function casts(): array
    {
        return ['expires_at' => 'immutable_datetime', 'accepted_at' => 'immutable_datetime'];
    }

    /**
     * @return BelongsTo<Person, $this>
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_id');
    }

    public function isUsable(): bool
    {
        return $this->accepted_at === null && $this->expires_at->isFuture();
    }
}
