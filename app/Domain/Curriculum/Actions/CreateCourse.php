<?php

namespace App\Domain\Curriculum\Actions;

use App\Domain\Security\Services\AuditLogger;
use App\Models\Curriculum\Course;
use App\Models\Curriculum\CourseVersion;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * A new course with its first version.
 */
class CreateCourse
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array<string, mixed>  $course  code, org_unit_id, is_active
     * @param  array<string, mixed>  $version  effective dates and ReviseCourse::ATTRIBUTES
     */
    public function handle(array $course, array $version, User $createdBy): Course
    {
        return DB::transaction(function () use ($course, $version, $createdBy) {
            $created = Course::query()->create(Arr::only($course, ['code', 'org_unit_id', 'is_active']));
            CourseVersion::query()->create([
                ...Arr::only($version, ['effective_start_date', 'effective_end_date', ...ReviseCourse::ATTRIBUTES]),
                'course_id' => $created->id,
            ]);

            $this->audit->record('course.created', $created, null, ['code' => $created->code], $createdBy);

            return $created;
        });
    }
}
