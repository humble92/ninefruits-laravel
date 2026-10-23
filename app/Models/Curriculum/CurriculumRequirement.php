<?php

namespace App\Models\Curriculum;

use App\Domain\Curriculum\Enums\RequirementKind;
use App\Models\Assessment\AssessmentType;
use App\Models\Concerns\HasTranslatableAttributes;
use App\Models\Contracts\Translatable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * One line of a requirement set: credits in a category, required courses, or a choice
 * from a group of courses.
 *
 * @property int $id
 * @property int $curriculum_requirement_set_id
 * @property RequirementKind $kind
 * @property string|null $name
 * @property int|null $course_category_id
 * @property int|null $assessment_type_id
 * @property string|null $min_credits
 * @property int|null $min_courses
 * @property int $sort_order
 * @property-read CurriculumRequirementSet $set
 * @property-read CourseCategory|null $category
 * @property-read AssessmentType|null $assessmentType
 * @property-read Collection<int, Course> $courses
 */
class CurriculumRequirement extends Model implements Translatable
{
    use HasTranslatableAttributes;

    /** @var list<string> */
    public array $translatable = ['name'];

    protected $fillable = ['curriculum_requirement_set_id', 'kind', 'name', 'course_category_id', 'assessment_type_id', 'min_credits', 'min_courses', 'sort_order'];

    protected $attributes = ['name' => null, 'course_category_id' => null, 'assessment_type_id' => null, 'min_credits' => null, 'min_courses' => null, 'sort_order' => 0];

    protected function casts(): array
    {
        return ['kind' => RequirementKind::class, 'min_credits' => 'decimal:1', 'min_courses' => 'integer', 'sort_order' => 'integer'];
    }

    /**
     * @return BelongsTo<CurriculumRequirementSet, $this>
     */
    public function set(): BelongsTo
    {
        return $this->belongsTo(CurriculumRequirementSet::class, 'curriculum_requirement_set_id');
    }

    /**
     * @return BelongsTo<CourseCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(CourseCategory::class, 'course_category_id');
    }

    /**
     * @return BelongsTo<AssessmentType, $this>
     */
    public function assessmentType(): BelongsTo
    {
        return $this->belongsTo(AssessmentType::class, 'assessment_type_id');
    }

    /**
     * @return BelongsToMany<Course, $this>
     */
    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'curriculum_requirement_courses')->withTimestamps()->orderBy('code');
    }
}
