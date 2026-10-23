<?php

namespace App\Models\Lms;

use App\Domain\Lms\Enums\SyncOperation;
use App\Domain\Lms\Enums\SyncStatus;
use App\Models\Offering\Section;
use App\Models\Registration\Enrollment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One synchronization step and its outcome.
 *
 * @property int $id
 * @property int $lms_platform_id
 * @property int|null $section_id
 * @property int|null $enrollment_id
 * @property int|null $user_id
 * @property SyncOperation $operation
 * @property SyncStatus $status
 * @property string|null $message
 * @property array<string, mixed>|null $payload
 * @property CarbonImmutable $started_at
 * @property CarbonImmutable|null $finished_at
 * @property-read LmsPlatform $platform
 * @property-read Section|null $section
 * @property-read Enrollment|null $enrollment
 * @property-read User|null $user
 */
class LmsSyncLog extends Model
{
    protected $fillable = ['lms_platform_id', 'section_id', 'enrollment_id', 'user_id', 'operation', 'status', 'message', 'payload', 'started_at', 'finished_at'];

    protected $attributes = ['section_id' => null, 'enrollment_id' => null, 'user_id' => null, 'message' => null, 'payload' => null, 'finished_at' => null];

    protected function casts(): array
    {
        return ['operation' => SyncOperation::class, 'status' => SyncStatus::class, 'payload' => 'array', 'started_at' => 'immutable_datetime', 'finished_at' => 'immutable_datetime'];
    }

    /**
     * @return BelongsTo<LmsPlatform, $this>
     */
    public function platform(): BelongsTo
    {
        return $this->belongsTo(LmsPlatform::class, 'lms_platform_id');
    }

    /**
     * @return BelongsTo<Section, $this>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * @return BelongsTo<Enrollment, $this>
     */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
