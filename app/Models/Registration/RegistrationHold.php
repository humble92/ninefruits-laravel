<?php

namespace App\Models\Registration;

use App\Domain\Registration\Enums\HoldType;
use App\Models\Person\StudentRecord;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A hold on a student record (legacy SRDENY): blocks registration, and from later phases
 * grade viewing or certificates.
 *
 * @property int $id
 * @property int $student_record_id
 * @property HoldType $hold_type
 * @property bool $blocks_registration
 * @property bool $blocks_grades
 * @property bool $blocks_certificates
 * @property CarbonImmutable $starts_at
 * @property CarbonImmutable|null $ends_at
 * @property string $reason
 * @property string $source
 * @property int|null $created_by_id
 * @property CarbonImmutable|null $released_at
 * @property int|null $released_by_id
 * @property string|null $release_reason
 * @property-read StudentRecord $studentRecord
 * @property-read User|null $createdBy
 */
class RegistrationHold extends Model
{
    protected $fillable = ['student_record_id', 'hold_type', 'blocks_registration', 'blocks_grades', 'blocks_certificates', 'starts_at', 'ends_at', 'reason', 'source', 'created_by_id'];

    protected $attributes = [
        'blocks_registration' => true, 'blocks_grades' => false, 'blocks_certificates' => false, 'ends_at' => null, 'source' => 'manual',
        'created_by_id' => null, 'released_at' => null, 'released_by_id' => null, 'release_reason' => null,
    ];

    protected function casts(): array
    {
        return [
            'hold_type' => HoldType::class,
            'blocks_registration' => 'boolean',
            'blocks_grades' => 'boolean',
            'blocks_certificates' => 'boolean',
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
            'released_at' => 'immutable_datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Holds in force at the time (not released, started, not ended).
     *
     * @param  Builder<self>  $query
     */
    public function scopeInForce(Builder $query, ?CarbonImmutable $at = null): void
    {
        $at ??= CarbonImmutable::now();

        $query->whereNull('released_at')->where('starts_at', '<=', $at)
            ->where(fn (Builder $query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', $at));
    }
}
