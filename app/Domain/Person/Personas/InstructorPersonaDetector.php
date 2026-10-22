<?php

namespace App\Domain\Person\Personas;

use App\Domain\Security\Contracts\PersonaDetector;
use App\Domain\Security\Enums\Persona;
use App\Models\Person\Instructor;
use App\Models\User;

/**
 * Instructor: has a current instructor record. Phase 4 adds "teaches a section".
 */
class InstructorPersonaDetector implements PersonaDetector
{
    public function persona(): Persona
    {
        return Persona::Instructor;
    }

    public function appliesTo(User $user): bool
    {
        return $user->person_id !== null
            && Instructor::query()->where('person_id', $user->person_id)->current()->exists();
    }
}
