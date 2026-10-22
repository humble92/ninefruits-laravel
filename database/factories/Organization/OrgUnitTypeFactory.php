<?php

namespace Database\Factories\Organization;

use App\Models\Organization\OrgUnitType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrgUnitType>
 */
class OrgUnitTypeFactory extends Factory
{
    protected $model = OrgUnitType::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->regexify('[a-z]{8}'),
            'name' => ['en' => fake()->word()],
        ];
    }
}
