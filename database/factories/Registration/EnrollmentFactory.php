<?php

namespace Database\Factories\Registration;

use App\Domain\Grading\Enums\GradeStatus;
use App\Domain\Registration\Enums\EnrollmentStatus;
use App\Models\Offering\Section;
use App\Models\Person\StudentRecord;
use App\Models\Registration\Enrollment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Enrollments created directly (no seat counting). Use EnrollStudent to test registration;
 * use this for history, e.g. completed() for passed prerequisites.
 *
 * @extends Factory<Enrollment>
 */
class EnrollmentFactory extends Factory
{
    protected $model = Enrollment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_record_id' => StudentRecord::factory(),
            'section_id' => Section::factory()->published(),
            'term_id' => fn (array $attributes) => Section::query()->findOrFail($attributes['section_id'])->term_id,
            'course_id' => fn (array $attributes) => Section::query()->findOrFail($attributes['section_id'])->course_id,
            'course_version_id' => fn (array $attributes) => Section::query()->findOrFail($attributes['section_id'])->course_version_id,
            'credits' => fn (array $attributes) => Section::query()->with('version')->findOrFail($attributes['section_id'])->version->credits,
            'enrolled_at' => now(),
        ];
    }

    public function inSection(Section $section): static
    {
        return $this->state(fn () => ['section_id' => $section->id]);
    }

    /**
     * Completed with a grade (Phase 6 sets these through grading).
     */
    public function completed(string $grade = 'A0', bool $passed = true): static
    {
        return $this->afterMaking(function (Enrollment $enrollment) use ($grade, $passed) {
            $enrollment->forceFill([
                'status' => EnrollmentStatus::Completed,
                'grade_status' => GradeStatus::Finalized,
                'grade_code' => $grade,
                'is_passed' => $passed,
            ]);
        });
    }
}
