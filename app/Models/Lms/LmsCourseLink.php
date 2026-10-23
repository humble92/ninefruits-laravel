<?php

namespace App\Models\Lms;

use App\Domain\Lms\Enums\LinkStatus;
use App\Models\Offering\Section;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A section's course on a platform.
 *
 * @property int $id
 * @property int $lms_platform_id
 * @property int $section_id
 * @property string|null $external_course_id
 * @property string|null $external_url
 * @property LinkStatus $status
 * @property CarbonImmutable|null $last_synced_at
 * @property string|null $last_error
 * @property-read LmsPlatform $platform
 * @property-read Section $section
 */
class LmsCourseLink extends Model
{
    protected $fillable = ['lms_platform_id', 'section_id', 'external_course_id', 'external_url', 'status', 'last_synced_at', 'last_error'];

    protected $attributes = ['external_course_id' => null, 'external_url' => null, 'status' => 'pending', 'last_synced_at' => null, 'last_error' => null];

    protected function casts(): array
    {
        return ['status' => LinkStatus::class, 'last_synced_at' => 'immutable_datetime'];
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
}
