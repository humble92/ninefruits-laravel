<?php

namespace App\Models\Registration;

use App\Domain\Registration\Enums\RuleCode;
use App\Models\Curriculum\Course;
use App\Models\Offering\Section;
use App\Models\Person\StudentRecord;
use App\Models\Term\Term;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A staff-granted exception to one rule for a student in a term, optionally limited to a
 * section or course (legacy SRALLOW). Only rules whose rule set allows overrides honour it.
 *
 * @property int $id
 * @property int $student_record_id
 * @property int $term_id
 * @property RuleCode $rule_code
 * @property int|null $section_id
 * @property int|null $course_id
 * @property string $reason
 * @property CarbonImmutable|null $expires_at
 * @property int|null $granted_by_id
 * @property CarbonImmutable|null $revoked_at
 * @property int|null $revoked_by_id
 * @property-read StudentRecord $studentRecord
 * @property-read Term $term
 * @property-read Section|null $section
 * @property-read Course|null $course
 * @property-read User|null $grantedBy
 */
class RegistrationOverride extends Model
{
    protected $fillable = ['student_record_id', 'term_id', 'rule_code', 'section_id', 'course_id', 'reason', 'expires_at', 'granted_by_id'];

    protected $attributes = ['section_id' => null, 'course_id' => null, 'expires_at' => null, 'granted_by_id' => null, 'revoked_at' => null, 'revoked_by_id' => null];

    protected function casts(): array
    {
        return ['rule_code' => RuleCode::class, 'expires_at' => 'immutable_datetime', 'revoked_at' => 'immutable_datetime'];
    }

    /**
     * @return BelongsTo<StudentRecord, $this>
     */
    public function studentRecord(): BelongsTo
    {
        return $this->belongsTo(StudentRecord::class);
    }

    /**
     * @return BelongsTo<Term, $this>
     */
    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    /**
     * @return BelongsTo<Section, $this>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by_id');
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeInForce(Builder $query, ?CarbonImmutable $at = null): void
    {
        $at ??= CarbonImmutable::now();

        $query->whereNull('revoked_at')->where(fn (Builder $query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', $at));
    }

    public function covers(Section $section): bool
    {
        return ($this->section_id === null || $this->section_id === $section->id)
            && ($this->course_id === null || $this->course_id === $section->course_id);
    }
}
