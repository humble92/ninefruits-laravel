<?php

namespace App\Models\Graduation;

use App\Models\Curriculum\CurriculumRequirement;
use App\Models\Person\StudentRecord;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A requirement waived for one student (transfer credit, change of major), with the reason
 * and who granted it. The audit counts a waived requirement as satisfied.
 *
 * @property int $id
 * @property int $student_record_id
 * @property int $curriculum_requirement_id
 * @property string $reason
 * @property int|null $granted_by_id
 * @property CarbonImmutable $granted_at
 * @property-read StudentRecord $studentRecord
 * @property-read CurriculumRequirement $requirement
 * @property-read User|null $grantedBy
 */
class DegreeRequirementWaiver extends Model
{
    protected $fillable = ['student_record_id', 'curriculum_requirement_id', 'reason', 'granted_by_id', 'granted_at'];

    protected $attributes = ['granted_by_id' => null];

    protected function casts(): array
    {
        return ['granted_at' => 'immutable_datetime'];
    }

    /**
     * @return BelongsTo<StudentRecord, $this>
     */
    public function studentRecord(): BelongsTo
    {
        return $this->belongsTo(StudentRecord::class);
    }

    /**
     * @return BelongsTo<CurriculumRequirement, $this>
     */
    public function requirement(): BelongsTo
    {
        return $this->belongsTo(CurriculumRequirement::class, 'curriculum_requirement_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by_id');
    }
}
