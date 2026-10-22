<?php

namespace Database\Factories\Facility;

use App\Models\Facility\Building;
use App\Models\Facility\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
{
    protected $model = Room::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'building_id' => Building::factory(),
            'code' => (string) fake()->unique()->numberBetween(100, 999),
            'capacity' => fake()->randomElement([20, 30, 40, 60, 80, 120]),
        ];
    }
}
