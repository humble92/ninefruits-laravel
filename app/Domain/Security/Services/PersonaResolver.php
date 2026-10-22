<?php

namespace App\Domain\Security\Services;

use App\Domain\Security\Contracts\PersonaDetector;
use App\Domain\Security\Enums\Persona;
use App\Models\User;

/**
 * Resolves the personas a user currently has. Bound as a scoped instance, so results are
 * cached per request or job.
 */
class PersonaResolver
{
    /** @var array<int, list<Persona>> */
    private array $resolved = [];

    /**
     * @param  iterable<PersonaDetector>  $detectors
     */
    public function __construct(private readonly iterable $detectors) {}

    /**
     * @return list<Persona>
     */
    public function personasFor(User $user): array
    {
        if (isset($this->resolved[$user->id])) {
            return $this->resolved[$user->id];
        }

        $personas = [];

        foreach ($this->detectors as $detector) {
            if ($detector->appliesTo($user)) {
                $personas[] = $detector->persona();
            }
        }

        return $this->resolved[$user->id] = array_values(array_unique($personas, SORT_REGULAR));
    }

    public function has(User $user, Persona $persona): bool
    {
        return in_array($persona, $this->personasFor($user), true);
    }

    public function flush(): void
    {
        $this->resolved = [];
    }
}
