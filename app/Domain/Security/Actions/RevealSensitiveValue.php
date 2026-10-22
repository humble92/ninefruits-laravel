<?php

namespace App\Domain\Security\Actions;

use App\Domain\Security\Enums\SecurityEventType;
use App\Models\Contracts\EncryptsPersonalData;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;

/**
 * Returns an unmasked personal data value and records who saw it. Callers check the
 * record scope (policy) first; this action checks the *.view_sensitive permission.
 */
class RevealSensitiveValue
{
    public function __construct(private readonly RecordSecurityEvent $recordSecurityEvent) {}

    public function handle(User $viewer, Model $record, string $attribute, string $permission, ?string $reason = null): ?string
    {
        if (! str_ends_with($permission, '.view_sensitive') || ! $viewer->can($permission)) {
            throw new AuthorizationException;
        }

        $value = $record instanceof EncryptsPersonalData && array_key_exists($attribute, $record->encryptedPiiAttributes())
            ? $record->getAttribute("{$attribute}_encrypted")
            : $record->getAttribute($attribute);

        $this->recordSecurityEvent->handle(
            SecurityEventType::SensitiveDataRevealed,
            $viewer,
            subject: $record,
            properties: ['attribute' => $attribute, 'reason' => $reason],
        );

        return $value;
    }
}
