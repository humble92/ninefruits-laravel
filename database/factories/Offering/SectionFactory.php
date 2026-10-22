<?php

namespace Database\Factories\Offering;

use App\Domain\Offering\Enums\SectionStatus;
use App\Models\Curriculum\Course;
use App\Models\Offering\Section;
use App\Models\Term\Term;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Draft sections of a course in the full-term session of a published term. Meetings and
 * instructors are added through the actions or directly in tests.
 *
 * @extends Factory<Section>
 */
class SectionFactory extends Factory
{
    protected $model = Section::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'term_id' => Term::factory()->published(),
            'term_session_id' => fn (array $attributes) => Term::query()->findOrFail($attributes['term_id'])->fullTermSession()->id,
            'course_id' => Course::factory(),
            'course_version_id' => fn (array $attributes) => Course::query()->findOrFail($attributes['course_id'])->versions()->value('id'),
            'section_code' => fake()->unique()->numerify('##'),
            'org_unit_id' => fn (array $attributes) => Course::query()->findOrFail($attributes['course_id'])->org_unit_id,
            'capacity' => 40,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => ['status' => SectionStatus::Published]);
    }

    public function forCourse(Course $course): static
    {
        return $this->state(fn () => ['course_id' => $course->id]);
    }

    public function inTerm(Term $term): static
    {
        return $this->state(fn () => ['term_id' => $term->id]);
    }
}
