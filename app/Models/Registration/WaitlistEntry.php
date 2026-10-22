<?php

namespace App\Models\Registration;

use App\Domain\Registration\Enums\WaitlistStatus;
use App\Models\Offering\Section;
use App\Models\Person\StudentRecord;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A student waiting for a seat. Entries are served in request order; each promotion
 * checks the registration rules again.
 *
 * @property int $id
 * @property int $section_id
 * @property int $student_record_id
 * @property WaitlistStatus $status
 * @property CarbonImmutable $requested_at
 * @property int|null $requested_by_id
 * @property CarbonImmutable|null $resolved_at
 * @property int|null $enrollment_id
 * @property string|null $outcome_note
 * @property-read Section $section
 * @property-read StudentRecord $studentRecord
 */
class WaitlistEntry extends Model
{
    protected $fillable = ['section_id', 'student_record_id', 'requested_at', 'requested_by_id'];

    protected $attributes = ['status' => 'waiting', 'requested_by_id' => null, 'resolved_at' => null, 'enrollment_id' => null, 'outcome_note' => null];

    protected function casts(): array
    {
        return ['status' => WaitlistStatus::class, 'requested_at' => 'immutable_datetime', 'resolved_at' => 'immutable_datetime'];
    }

    /**
     * @return BelongsTo<Section, $this>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * @return BelongsTo<StudentRecord, $this>
     */
    public function studentRecord(): BelongsTo
    {
        return $this->belongsTo(StudentRecord::class);
    }

    /**
     * Position among waiting entries of the section (1 = next).
     */
    public function position(): int
    {
        return self::query()->where('section_id', $this->section_id)->where('status', WaitlistStatus::Waiting)
            ->where('requested_at', '<=', $this->requested_at)->count();
    }
}
