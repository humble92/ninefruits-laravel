<?php

namespace App\Domain\Person\Personas;

use App\Domain\Security\Contracts\PersonaDetector;
use App\Domain\Security\Enums\Persona;
use App\Models\Person\AdvisorAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Advisor: advises at least one student today.
 */
class AdvisorPersonaDetector implements PersonaDetector
{
    public function persona(): Persona
    {
        return Persona::Advisor;
    }

    public function appliesTo(User $user): bool
    {
        return $user->person_id !== null
            && AdvisorAssignment::query()->current()
                ->whereHas('instructor', fn (Builder $instructor) => $instructor->where('person_id', $user->person_id))
                ->exists();
    }
}
