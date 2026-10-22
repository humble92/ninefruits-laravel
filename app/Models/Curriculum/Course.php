<?php

namespace App\Models\Curriculum;

use App\Models\Organization\OrgUnit;
use Carbon\CarbonImmutable;
use Database\Factories\Curriculum\CourseFactory;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A course's identity (legacy SFOBJECT code). Everything that changes over time (title,
 * credits, category, requisites) is on its versions.
 *
 * @property int $id
 * @property string $code
 * @property int $org_unit_id
 * @property bool $is_active
 * @property-read OrgUnit $orgUnit
 * @property-read Collection<int, CourseVersion> $versions
 */
class Course extends Model
{
    /** @use HasFactory<CourseFactory> */
    use HasFactory;

    protected $fillable = ['code', 'org_unit_id', 'is_active'];

    protected $attributes = ['is_active' => true];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /**
     * @return BelongsTo<OrgUnit, $this>
     */
    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class);
    }

    /**
     * @return HasMany<CourseVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(CourseVersion::class)->orderByDesc('effective_start_date');
    }

    /**
     * @return HasMany<CourseEquivalency, $this>
     */
    public function equivalencies(): HasMany
    {
        return $this->hasMany(CourseEquivalency::class);
    }

    public function versionOn(DateTimeInterface|string|null $date = null): ?CourseVersion
    {
        return CourseVersion::query()->where('course_id', $this->id)->effectiveOn($date ?? CarbonImmutable::today())->first();
    }
}
