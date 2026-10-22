<?php

namespace App\Domain\Person\Personas;

use App\Domain\Security\Contracts\PersonaDetector;
use App\Domain\Security\Enums\Persona;
use App\Models\Person\StudentRecord;
use App\Models\User;

/**
 * Student: has a current (enrolled or on leave) student record.
 */
class StudentPersonaDetector implements PersonaDetector
{
    public function persona(): Persona
    {
        return Persona::Student;
    }

    public function appliesTo(User $user): bool
    {
        return $user->person_id !== null
            && StudentRecord::query()->where('person_id', $user->person_id)->active()->exists();
    }
}
