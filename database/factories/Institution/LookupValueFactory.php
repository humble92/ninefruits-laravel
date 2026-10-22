<?php

namespace Database\Factories\Institution;

use App\Models\Institution\LookupType;
use App\Models\Institution\LookupValue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LookupValue>
 */
class LookupValueFactory extends Factory
{
    protected $model = LookupValue::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lookup_type_id' => LookupType::factory(),
            'code' => fake()->unique()->regexify('[a-z]{6}'),
            'name' => ['en' => fake()->word()],
        ];
    }
}
