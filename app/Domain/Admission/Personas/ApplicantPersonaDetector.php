<?php

namespace App\Domain\Admission\Personas;

use App\Domain\Admission\Enums\ApplicationStatus;
use App\Domain\Security\Contracts\PersonaDetector;
use App\Domain\Security\Enums\Persona;
use App\Models\Admission\Application;
use App\Models\User;

/**
 * Applicant: has an application that is still open and not yet matriculated. A student or
 * graduate applying again is an applicant too.
 */
class ApplicantPersonaDetector implements PersonaDetector
{
    public function persona(): Persona
    {
        return Persona::Applicant;
    }

    public function appliesTo(User $user): bool
    {
        return $user->person_id !== null
            && Application::query()
                ->where('person_id', $user->person_id)
                ->whereIn('status', ApplicationStatus::openValues())
                ->whereNull('student_record_id')
                ->exists();
    }
}
