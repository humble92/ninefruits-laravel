<?php

namespace App\Domain\Security\Actions;

use App\Domain\Security\Enums\SecurityEventType;
use App\Models\Security\SecurityEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Context;

class RecordSecurityEvent
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public function handle(
        SecurityEventType $type,
        ?User $user = null,
        ?string $identifier = null,
        ?Model $subject = null,
        array $properties = [],
    ): SecurityEvent {
        return SecurityEvent::query()->create([
            'type' => $type,
            'user_id' => $user?->id,
            'identifier' => $identifier,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'properties' => $properties === [] ? null : $properties,
            'request_id' => Context::get('request_id'),
            'ip_address' => Context::get('ip_address'),
            'user_agent' => Context::get('user_agent'),
        ]);
    }
}
