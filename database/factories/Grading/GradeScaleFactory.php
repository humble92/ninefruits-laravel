<?php

namespace Database\Factories\Grading;

use App\Models\Grading\GradeScale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GradeScale>
 */
class GradeScaleFactory extends Factory
{
    protected $model = GradeScale::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->regexify('[A-Z]{2}[0-9]'),
            'name' => ['en' => 'Grade scale'],
            'max_grade_points' => 4.5,
        ];
    }
}
