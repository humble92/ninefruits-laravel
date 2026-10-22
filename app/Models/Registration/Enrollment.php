<?php

namespace App\Models\Registration;

use App\Domain\Grading\Enums\GradeStatus;
use App\Domain\Registration\Enums\EnrollmentSource;
use App\Domain\Registration\Enums\EnrollmentStatus;
use App\Models\Curriculum\Course;
use App\Models\Curriculum\CourseVersion;
use App\Models\Offering\Section;
use App\Models\Offering\SectionSeatGroup;
use App\Models\Person\StudentRecord;
use App\Models\Term\Term;
use Carbon\CarbonImmutable;
use Database\Factories\Registration\EnrollmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A student record in a section (legacy SSCORE): registration now, grades from Phase 6.
 * Credits, version and category are a snapshot of the course as taken.
 *
 * @property int $id
 * @property int $student_record_id
 * @property int $section_id
 * @property int $term_id
 * @property int $course_id
 * @property int $course_version_id
 * @property string $credits
 * @property int|null $course_category_id
 * @property bool $counts_toward_credit_limit
 * @property EnrollmentStatus $status
 * @property int|null $seat_group_id
 * @property bool $is_retake
 * @property int|null $repeats_enrollment_id
 * @property EnrollmentSource $source
 * @property CarbonImmutable $enrolled_at
 * @property CarbonImmutable|null $dropped_at
 * @property CarbonImmutable|null $withdrawn_at
 * @property int|null $changed_by_id
 * @property GradeStatus $grade_status
 * @property string|null $grade_code
 * @property bool|null $is_passed
 * @property-read StudentRecord $studentRecord
 * @property-read Section $section
 * @property-read Term $term
 * @property-read Course $course
 * @property-read CourseVersion $version
 * @property-read SectionSeatGroup|null $seatGroup
 */
class Enrollment extends Model
{
    /** @use HasFactory<EnrollmentFactory> */
    use HasFactory;

    protected $fillable = [
        'student_record_id', 'section_id', 'term_id', 'course_id', 'course_version_id', 'credits', 'course_category_id',
        'counts_toward_credit_limit', 'seat_group_id', 'is_retake', 'repeats_enrollment_id', 'source', 'enrolled_at', 'changed_by_id',
    ];

    protected $attributes = [
        'course_category_id' => null, 'counts_toward_credit_limit' => true, 'status' => 'enrolled', 'seat_group_id' => null,
        'is_retake' => false, 'repeats_enrollment_id' => null, 'source' => 'student', 'dropped_at' => null, 'withdrawn_at' => null,
        'changed_by_id' => null, 'grade_status' => 'none', 'grade_code' => null, 'is_passed' => null,
    ];

    protected function casts(): array
    {
        return [
            'credits' => 'decimal:1',
            'counts_toward_credit_limit' => 'boolean',
            'status' => EnrollmentStatus::class,
            'is_retake' => 'boolean',
            'source' => EnrollmentSource::class,
            'enrolled_at' => 'immutable_datetime',
            'dropped_at' => 'immutable_datetime',
            'withdrawn_at' => 'immutable_datetime',
            'grade_status' => GradeStatus::class,
            'is_passed' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<StudentRecord, $this>
     */
    public function studentRecord(): BelongsTo
    {
        return $this->belongsTo(StudentRecord::class);
    }

    /**
     * @return BelongsTo<Section, $this>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * @return BelongsTo<Term, $this>
     */
    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return BelongsTo<CourseVersion, $this>
     */
    public function version(): BelongsTo
    {
        return $this->belongsTo(CourseVersion::class, 'course_version_id');
    }

    /**
     * @return BelongsTo<SectionSeatGroup, $this>
     */
    public function seatGroup(): BelongsTo
    {
        return $this->belongsTo(SectionSeatGroup::class, 'seat_group_id');
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', EnrollmentStatus::Enrolled);
    }
}
