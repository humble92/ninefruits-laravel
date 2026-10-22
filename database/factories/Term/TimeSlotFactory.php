<?php

namespace Database\Factories\Term;

use App\Models\Term\TimeSlot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TimeSlot>
 */
class TimeSlotFactory extends Factory
{
    protected $model = TimeSlot::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => (string) fake()->unique()->numberBetween(1, 99),
            'name' => ['en' => 'Period'],
            'start_time' => '09:00',
            'end_time' => '09:50',
            'weekdays' => [1, 2, 3, 4, 5],
        ];
    }
}
