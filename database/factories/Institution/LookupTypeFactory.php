<?php

namespace Database\Factories\Institution;

use App\Models\Institution\LookupType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LookupType>
 */
class LookupTypeFactory extends Factory
{
    protected $model = LookupType::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->regexify('[a-z]{8}'),
            'name' => ['en' => fake()->words(2, true)],
        ];
    }
}
