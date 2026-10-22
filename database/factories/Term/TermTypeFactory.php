<?php

namespace Database\Factories\Term;

use App\Models\Term\TermType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TermType>
 */
class TermTypeFactory extends Factory
{
    protected $model = TermType::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->regexify('[a-z]{6}'),
            'name' => ['en' => fake()->word()],
        ];
    }
}
