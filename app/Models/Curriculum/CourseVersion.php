<?php

namespace App\Models\Curriculum;

use App\Domain\Curriculum\Enums\GradingBasis;
use App\Models\Concerns\HasEffectivePeriod;
use App\Models\Concerns\HasTranslatableAttributes;
use App\Models\Contracts\Translatable;
use Carbon\CarbonImmutable;
use Database\Factories\Curriculum\CourseVersionFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A course as defined for a period. Periods of one course never overlap (exclusion
 * constraint), so a date names exactly one version. Sections record the version taught.
 *
 * @property int $id
 * @property int $course_id
 * @property CarbonImmutable $effective_start_date
 * @property CarbonImmutable|null $effective_end_date
 * @property string $title
 * @property string|null $description
 * @property string $credits
 * @property string $lecture_hours
 * @property string $lab_hours
 * @property int|null $course_category_id
 * @property GradingBasis $grading_basis
 * @property bool $counts_toward_credit_limit
 * @property bool $is_repeatable
 * @property-read Course $course
 * @property-read CourseCategory|null $category
 * @property-read Collection<int, CourseRequisite> $requisites
 */
class CourseVersion extends Model implements Translatable
{
    /** @use HasFactory<CourseVersionFactory> */
    use HasEffectivePeriod, HasFactory, HasTranslatableAttributes;

    /** @var list<string> */
    public array $translatable = ['title', 'description'];

    protected $fillable = [
        'course_id', 'effective_start_date', 'effective_end_date', 'title', 'description', 'credits', 'lecture_hours', 'lab_hours',
        'course_category_id', 'grading_basis', 'counts_toward_credit_limit', 'is_repeatable',
    ];

    protected $attributes = [
        'effective_end_date' => null, 'description' => null, 'lecture_hours' => 0, 'lab_hours' => 0, 'course_category_id' => null,
        'grading_basis' => 'graded', 'counts_toward_credit_limit' => true, 'is_repeatable' => false,
    ];

    protected function casts(): array
    {
        return [
            'effective_start_date' => 'immutable_date',
            'effective_end_date' => 'immutable_date',
            'credits' => 'decimal:1',
            'lecture_hours' => 'decimal:1',
            'lab_hours' => 'decimal:1',
            'grading_basis' => GradingBasis::class,
            'counts_toward_credit_limit' => 'boolean',
            'is_repeatable' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return BelongsTo<CourseCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(CourseCategory::class, 'course_category_id');
    }

    /**
     * @return HasMany<CourseRequisite, $this>
     */
    public function requisites(): HasMany
    {
        return $this->hasMany(CourseRequisite::class)->orderBy('kind')->orderBy('group_number');
    }

    /**
     * "CSE2010 Data Structures (3.0)" for lists and selects.
     */
    public function label(): string
    {
        return sprintf('%s %s (%s)', $this->course->code, $this->title, $this->credits);
    }
}
