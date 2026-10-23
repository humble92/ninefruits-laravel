<?php

namespace App\Domain\Curriculum\Actions;

use App\Domain\Security\Services\AuditLogger;
use App\Models\Curriculum\Course;
use App\Models\Curriculum\CourseRequisite;
use App\Models\Curriculum\CourseVersion;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * A new version of a course from a date (e.g. credits change from 2027): the version in
 * effect ends the day before and the new one starts with a copy of its data and requisites,
 * overridden by the given attributes. Sections already offered keep their version.
 */
class ReviseCourse
{
    public const array ATTRIBUTES = ['title', 'description', 'credits', 'lecture_hours', 'lab_hours', 'course_category_id', 'grading_basis', 'counts_toward_credit_limit', 'is_repeatable', 'requires_evaluation'];

    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Course $course, CarbonImmutable $effectiveFrom, array $attributes, User $revisedBy, ?string $reason = null): CourseVersion
    {
        $current = $course->versionOn($effectiveFrom);

        if ($current === null) {
            throw ValidationException::withMessages(['effective_start_date' => __('curriculum.errors.no_version_to_revise')]);
        }

        if ($current->effective_start_date->equalTo($effectiveFrom)) {
            throw ValidationException::withMessages(['effective_start_date' => __('curriculum.errors.revise_same_start')]);
        }

        return DB::transaction(function () use ($course, $effectiveFrom, $attributes, $revisedBy, $reason, $current) {
            $previousEnd = $current->effective_end_date;
            $current->update(['effective_end_date' => $effectiveFrom->subDay()]);

            $version = new CourseVersion([
                ...Arr::only($current->getAttributes(), self::ATTRIBUTES),
                'title' => $current->getTranslations('title'),
                'description' => $current->getTranslations('description'),
                ...Arr::only($attributes, self::ATTRIBUTES),
                'course_id' => $course->id,
                'effective_start_date' => $effectiveFrom,
                'effective_end_date' => $previousEnd,
            ]);
            $version->save();

            foreach ($current->requisites()->get() as $requisite) {
                /** @var CourseRequisite $requisite */
                $version->requisites()->create($requisite->only(['kind', 'group_number', 'required_course_id', 'minimum_grade', 'allows_concurrent']));
            }

            $this->audit->record('course.revised', $version, $reason, [
                'course' => $course->code,
                'previous_version_id' => $current->id,
                'effective_from' => $effectiveFrom->toDateString(),
            ], $revisedBy);

            return $version;
        });
    }
}
