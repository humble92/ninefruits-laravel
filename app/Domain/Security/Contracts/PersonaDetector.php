<?php

namespace App\Domain\Security\Contracts;

use App\Domain\Security\Enums\Persona;
use App\Models\User;

/**
 * Decides from domain data whether a user currently has a persona.
 * Each domain context registers its detector (Phase 2: student, alumni, instructor; Phase 3: applicant).
 */
interface PersonaDetector
{
    public function persona(): Persona;

    public function appliesTo(User $user): bool;
}
