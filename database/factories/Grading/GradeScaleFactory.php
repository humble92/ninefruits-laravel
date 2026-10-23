<?php

namespace Database\Factories\Grading;

use App\Models\Grading\GradeScale;
use App\Models\Grading\GradeScaleItem;
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

    /**
     * The 4.5 scale with A+ to F, P and NP, as the default scale.
     */
    public function korean(): static
    {
        return $this->state(['max_grade_points' => 4.5, 'is_default' => true])->afterCreating(function (GradeScale $scale) {
            $items = [
                'A+' => [4.5, 95, 100, true], 'A0' => [4.0, 90, 94.99, true], 'B+' => [3.5, 85, 89.99, true], 'B0' => [3.0, 80, 84.99, true],
                'C+' => [2.5, 75, 79.99, true], 'C0' => [2.0, 70, 74.99, true], 'D+' => [1.5, 65, 69.99, true], 'D0' => [1.0, 60, 64.99, true],
                'F' => [0.0, 0, 59.99, false], 'P' => [null, null, null, true], 'NP' => [null, null, null, false],
            ];
            $order = 0;

            foreach ($items as $code => [$points, $min, $max, $passing]) {
                GradeScaleItem::query()->create([
                    'grade_scale_id' => $scale->id, 'code' => $code, 'grade_points' => $points, 'min_percentage' => $min, 'max_percentage' => $max,
                    'counts_in_gpa' => $points !== null, 'is_passing' => $passing, 'applies_to' => $points !== null ? 'graded' : 'pass_fail', 'sort_order' => $order += 10,
                ]);
            }
        });
    }
}
