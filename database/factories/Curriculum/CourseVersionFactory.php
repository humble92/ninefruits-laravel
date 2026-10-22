<?php

namespace Database\Factories\Curriculum;

use App\Models\Curriculum\Course;
use App\Models\Curriculum\CourseVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseVersion>
 */
class CourseVersionFactory extends Factory
{
    protected $model = CourseVersion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $topic = ucwords(fake()->words(2, true));

        return [
            'course_id' => Course::factory(),
            'effective_start_date' => '2000-03-01',
            'title' => ['en' => "Introduction to {$topic}"],
            'credits' => 3,
            'lecture_hours' => 3,
        ];
    }

    public function between(string $start, ?string $end = null): static
    {
        return $this->state(fn () => ['effective_start_date' => $start, 'effective_end_date' => $end]);
    }
}
