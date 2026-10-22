<?php

namespace Database\Factories\Curriculum;

use App\Models\Curriculum\CourseCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseCategory>
 */
class CourseCategoryFactory extends Factory
{
    protected $model = CourseCategory::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->regexify('[A-Z]{4}'),
            'name' => ['en' => fake()->words(2, true)],
        ];
    }
}
