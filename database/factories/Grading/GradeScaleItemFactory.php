<?php

namespace Database\Factories\Grading;

use App\Models\Grading\GradeScale;
use App\Models\Grading\GradeScaleItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GradeScaleItem>
 */
class GradeScaleItemFactory extends Factory
{
    protected $model = GradeScaleItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'grade_scale_id' => GradeScale::factory(),
            'code' => fake()->unique()->regexify('[A-F][+0]'),
            'grade_points' => 4.0,
        ];
    }
}
