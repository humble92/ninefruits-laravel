<?php

namespace App\Domain\Person\Personas;

use App\Domain\Person\Enums\StudentStatus;
use App\Domain\Security\Contracts\PersonaDetector;
use App\Domain\Security\Enums\Persona;
use App\Models\Person\StudentRecord;
use App\Models\User;

/**
 * Alumni: has a graduated student record (also while studying again on a new record).
 */
class AlumniPersonaDetector implements PersonaDetector
{
    public function persona(): Persona
    {
        return Persona::Alumni;
    }

    public function appliesTo(User $user): bool
    {
        return $user->person_id !== null
            && StudentRecord::query()->where('person_id', $user->person_id)->where('status', StudentStatus::Graduated)->exists();
    }
}
