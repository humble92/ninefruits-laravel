<?php

namespace Database\Factories\Person;

use App\Domain\Person\Enums\GuardianRelationship;
use App\Models\Person\Guardian;
use App\Models\Person\Person;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Guardian>
 */
class GuardianFactory extends Factory
{
    protected $model = Guardian::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'person_id' => Person::factory(),
            'relationship' => fake()->randomElement([GuardianRelationship::Father, GuardianRelationship::Mother]),
            'full_name' => fake()->name(),
            'phone' => fake()->numerify('010-####-####'),
        ];
    }
}
