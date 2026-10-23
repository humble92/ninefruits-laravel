<?php

namespace App\Models\Lms;

use App\Domain\Lms\Enums\LmsDriver;
use App\Models\Concerns\HasTranslatableAttributes;
use App\Models\Contracts\Translatable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An LMS the institution connects to: the driver, the endpoint, the (encrypted)
 * credentials and what is synchronized.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property LmsDriver $driver
 * @property string|null $base_url
 * @property array<string, string>|null $credentials
 * @property array<string, mixed> $options
 * @property bool $is_active
 * @property bool $should_sync_enrollments
 * @property bool $should_pull_grades
 * @property-read Collection<int, LmsCourseLink> $courseLinks
 * @property-read Collection<int, LmsSyncLog> $syncLogs
 */
class LmsPlatform extends Model implements Translatable
{
    use HasTranslatableAttributes;

    /** @var list<string> */
    public array $translatable = ['name'];

    protected $fillable = ['code', 'name', 'driver', 'base_url', 'credentials', 'options', 'is_active', 'should_sync_enrollments', 'should_pull_grades'];

    protected $attributes = ['driver' => 'null', 'base_url' => null, 'credentials' => null, 'options' => '{}', 'is_active' => false, 'should_sync_enrollments' => true, 'should_pull_grades' => false];

    protected $hidden = ['credentials'];

    protected function casts(): array
    {
        return ['driver' => LmsDriver::class, 'credentials' => 'encrypted:array', 'options' => 'array', 'is_active' => 'boolean', 'should_sync_enrollments' => 'boolean', 'should_pull_grades' => 'boolean'];
    }

    /**
     * @return HasMany<LmsCourseLink, $this>
     */
    public function courseLinks(): HasMany
    {
        return $this->hasMany(LmsCourseLink::class);
    }

    /**
     * @return HasMany<LmsSyncLog, $this>
     */
    public function syncLogs(): HasMany
    {
        return $this->hasMany(LmsSyncLog::class);
    }
}
