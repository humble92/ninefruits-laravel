<?php

namespace App\Models\Person;

use App\Domain\Person\Enums\AdvisorRole;
use Carbon\CarbonImmutable;
use Database\Factories\Person\AdvisorAssignmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Advisor of a student record for a period (legacy SR_ADVISER, LEADING_PROFESSOR).
 *
 * @property int $id
 * @property int $student_record_id
 * @property int $instructor_id
 * @property AdvisorRole $role
 * @property CarbonImmutable $start_date
 * @property CarbonImmutable|null $end_date
 * @property-read Instructor $instructor
 * @property-read StudentRecord $studentRecord
 */
class AdvisorAssignment extends Model
{
    /** @use HasFactory<AdvisorAssignmentFactory> */
    use HasFactory;

    protected $fillable = ['student_record_id', 'instructor_id', 'role', 'start_date', 'end_date'];

    protected $attributes = ['role' => 'primary', 'end_date' => null];

    protected function casts(): array
    {
        return ['role' => AdvisorRole::class, 'start_date' => 'immutable_date', 'end_date' => 'immutable_date'];
    }

    /**
     * @return BelongsTo<Instructor, $this>
     */
    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class);
    }

    /**
     * @return BelongsTo<StudentRecord, $this>
     */
    public function studentRecord(): BelongsTo
    {
        return $this->belongsTo(StudentRecord::class);
    }

    /**
     * Assignments in effect today.
     *
     * @param  Builder<self>  $query
     */
    public function scopeCurrent(Builder $query): void
    {
        $today = CarbonImmutable::today()->toDateString();

        $query->whereDate('start_date', '<=', $today)
            ->where(fn (Builder $query) => $query->whereNull('end_date')->orWhereDate('end_date', '>=', $today));
    }
}
