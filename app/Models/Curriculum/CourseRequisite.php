<?php

namespace App\Models\Curriculum;

use App\Domain\Curriculum\Enums\RequisiteKind;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A requisite of a course version. Courses in one group are alternatives; all groups of a
 * kind must be met. Phase 5 checks them at registration (with equivalent courses).
 *
 * @property int $id
 * @property int $course_version_id
 * @property RequisiteKind $kind
 * @property int $group_number
 * @property int $required_course_id
 * @property string|null $minimum_grade
 * @property bool $allows_concurrent
 * @property-read CourseVersion $courseVersion
 * @property-read Course $requiredCourse
 */
class CourseRequisite extends Model
{
    protected $fillable = ['course_version_id', 'kind', 'group_number', 'required_course_id', 'minimum_grade', 'allows_concurrent'];

    protected $attributes = ['group_number' => 1, 'minimum_grade' => null, 'allows_concurrent' => false];

    protected function casts(): array
    {
        return ['kind' => RequisiteKind::class, 'group_number' => 'integer', 'allows_concurrent' => 'boolean'];
    }

    /**
     * @return BelongsTo<CourseVersion, $this>
     */
    public function courseVersion(): BelongsTo
    {
        return $this->belongsTo(CourseVersion::class);
    }

    /**
     * @return BelongsTo<Course, $this>
     */
    public function requiredCourse(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'required_course_id');
    }
}
