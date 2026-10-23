<?php

namespace Database\Factories\Registration;

use App\Domain\Grading\Enums\GradeStatus;
use App\Domain\Registration\Enums\EnrollmentStatus;
use App\Models\Grading\GradeScale;
use App\Models\Grading\GradeScaleItem;
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
            'course_category_id' => fn (array $attributes) => Section::query()->with('version')->findOrFail($attributes['section_id'])->version->course_category_id,
            'enrolled_at' => now(),
        ];
    }

    public function inSection(Section $section): static
    {
        return $this->state(fn () => ['section_id' => $section->id]);
    }

    /**
     * Completed with a finalized grade of the default scale (a 4.5 scale is created when
     * there is none). Grading actions set these in the application.
     */
    public function completed(string $grade = 'A0', ?bool $passed = null): static
    {
        return $this->afterMaking(function (Enrollment $enrollment) use ($grade, $passed) {
            $scale = GradeScale::query()->where('is_default', true)->first() ?? GradeScale::factory()->korean()->create();
            $item = GradeScaleItem::query()->where('grade_scale_id', $scale->id)->where('code', $grade)->first();

            $enrollment->forceFill([
                'status' => EnrollmentStatus::Completed,
                'grade_status' => GradeStatus::Finalized,
                'grade_scale_id' => $scale->id,
                'grade_code' => $grade,
                'grade_points' => $item?->grade_points,
                'counts_in_gpa' => $item->counts_in_gpa ?? false,
                'is_passed' => $passed ?? $item->is_passing ?? true,
                'grade_finalized_at' => now(),
            ]);
        });
    }
}
