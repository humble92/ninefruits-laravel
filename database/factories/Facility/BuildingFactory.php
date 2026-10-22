<?php

namespace Database\Factories\Facility;

use App\Models\Facility\Building;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Building>
 */
class BuildingFactory extends Factory
{
    protected $model = Building::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->regexify('B[0-9]{2}'),
            'name' => ['en' => fake()->lastName().' Hall'],
        ];
    }
}
