<?php

namespace App\Models\Person;

use App\Domain\Person\Enums\StatusChangeStatus;
use App\Domain\Person\Enums\StudentStatus;
use App\Models\Term\Term;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Factories\Person\StudentStatusChangeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Requested or recorded status change of a student record (legacy SRALTER). Kept forever as
 * the record's status history.
 *
 * @property int $id
 * @property int $student_record_id
 * @property int $status_change_type_id
 * @property StudentStatus $from_status
 * @property StudentStatus $to_status
 * @property CarbonImmutable $effective_date
 * @property int|null $term_id
 * @property string $reason
 * @property StatusChangeStatus $status
 * @property int|null $requested_by_id
 * @property CarbonImmutable $requested_at
 * @property int|null $decided_by_id
 * @property CarbonImmutable|null $decided_at
 * @property string|null $decision_note
 * @property-read StudentRecord $studentRecord
 * @property-read StudentStatusChangeType $type
 */
class StudentStatusChange extends Model
{
    /** @use HasFactory<StudentStatusChangeFactory> */
    use HasFactory;

    protected $fillable = [
        'student_record_id', 'status_change_type_id', 'from_status', 'to_status', 'effective_date', 'term_id', 'reason',
        'requested_by_id', 'requested_at',
    ];

    protected $attributes = ['status' => 'requested', 'term_id' => null, 'decided_by_id' => null, 'decided_at' => null, 'decision_note' => null];

    protected function casts(): array
    {
        return [
            'from_status' => StudentStatus::class,
            'to_status' => StudentStatus::class,
            'status' => StatusChangeStatus::class,
            'effective_date' => 'immutable_date',
            'requested_at' => 'immutable_datetime',
            'decided_at' => 'immutable_datetime',
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
     * @return BelongsTo<StudentStatusChangeType, $this>
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(StudentStatusChangeType::class, 'status_change_type_id');
    }

    /**
     * @return BelongsTo<Term, $this>
     */
    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by_id');
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopePending(Builder $query): void
    {
        self::constrainToPending($query);
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     */
    public static function constrainToPending(Builder $query): void
    {
        $query->where('status', StatusChangeStatus::Requested);
    }
}
