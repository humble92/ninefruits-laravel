<?php

namespace App\Models\Grading;

use App\Domain\Grading\Enums\AcademicStanding;
use App\Models\Person\StudentRecord;
use App\Models\Term\Term;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A student's credits, GPA and standing for a term, with cumulative values up to that term
 * (the legacy computed these live in views). Calculated from finalized grades; term close
 * makes it the official snapshot (closed_at).
 *
 * @property int $id
 * @property int $student_record_id
 * @property int $term_id
 * @property string $attempted_credits
 * @property string $earned_credits
 * @property string $gpa_credits
 * @property string $grade_points_total
 * @property string|null $term_gpa
 * @property string $cumulative_attempted_credits
 * @property string $cumulative_earned_credits
 * @property string $cumulative_gpa_credits
 * @property string $cumulative_grade_points_total
 * @property string|null $cumulative_gpa
 * @property AcademicStanding|null $academic_standing
 * @property int $warning_count
 * @property bool $is_honors
 * @property int|null $honors_rank
 * @property CarbonImmutable $calculated_at
 * @property CarbonImmutable|null $closed_at
 * @property-read StudentRecord $studentRecord
 * @property-read Term $term
 */
class TermRecord extends Model
{
    protected $fillable = [
        'student_record_id', 'term_id', 'attempted_credits', 'earned_credits', 'gpa_credits', 'grade_points_total', 'term_gpa',
        'cumulative_attempted_credits', 'cumulative_earned_credits', 'cumulative_gpa_credits', 'cumulative_grade_points_total', 'cumulative_gpa',
        'academic_standing', 'warning_count', 'is_honors', 'honors_rank', 'calculated_at',
    ];

    protected $attributes = ['term_gpa' => null, 'cumulative_gpa' => null, 'academic_standing' => null, 'warning_count' => 0, 'is_honors' => false, 'honors_rank' => null, 'closed_at' => null];

    protected function casts(): array
    {
        return [
            'academic_standing' => AcademicStanding::class,
            'is_honors' => 'boolean',
            'calculated_at' => 'immutable_datetime',
            'closed_at' => 'immutable_datetime',
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
     * @return BelongsTo<Term, $this>
     */
    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }
}
