<?php

namespace Database\Factories\Institution;

use App\Models\Institution\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Country>
 */
class CountryFactory extends Factory
{
    protected $model = Country::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->lexify('??'),
            'alpha3_code' => fake()->unique()->lexify('???'),
            'name' => ['en' => fake()->country()],
        ];
    }
}
