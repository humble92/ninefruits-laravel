<?php

namespace Database\Factories\Curriculum;

use App\Models\Curriculum\Course;
use App\Models\Curriculum\CourseVersion;
use App\Models\Organization\OrgUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Courses with one open-ended version from 2000 (withVersion() for other details).
 *
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    protected $model = Course::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->regexify('[A-Z]{3}[1-4][0-9]{3}'),
            'org_unit_id' => OrgUnit::factory(),
        ];
    }

    /**
     * Replaces the default version with one with these attributes.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function withVersion(array $attributes = []): static
    {
        return $this->afterCreating(function (Course $course) use ($attributes) {
            $course->versions()->delete();
            CourseVersion::factory()->create(['course_id' => $course->id, ...$attributes]);
        });
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Course $course) {
            if (! $course->versions()->exists()) {
                CourseVersion::factory()->create(['course_id' => $course->id]);
            }
        });
    }
}
