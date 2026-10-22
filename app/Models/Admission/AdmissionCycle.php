<?php

namespace App\Models\Admission;

use App\Models\Concerns\HasTranslatableAttributes;
use App\Models\Contracts\Translatable;
use Carbon\CarbonImmutable;
use Database\Factories\Admission\AdmissionCycleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An admission round, e.g. "2027 Spring regular admission" or a rolling cycle with no
 * deadline (closes_at null). Separate from terms: the admission term is chosen when an
 * admitted applicant is matriculated.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property CarbonImmutable $opens_at
 * @property CarbonImmutable|null $closes_at
 * @property bool $is_published
 * @property bool $should_release_decisions_immediately
 * @property int|null $offer_response_days
 * @property-read Collection<int, AdmissionCycleProgram> $programs
 * @property-read Collection<int, AdmissionCycleDocumentType> $documentTypes
 */
class AdmissionCycle extends Model implements Translatable
{
    /** @use HasFactory<AdmissionCycleFactory> */
    use HasFactory, HasTranslatableAttributes;

    /** @var list<string> */
    public array $translatable = ['name', 'description'];

    protected $fillable = ['code', 'name', 'description', 'opens_at', 'closes_at', 'is_published', 'should_release_decisions_immediately', 'offer_response_days'];

    protected $attributes = [
        'description' => null, 'closes_at' => null, 'is_published' => false, 'should_release_decisions_immediately' => false,
        'offer_response_days' => null,
    ];

    protected function casts(): array
    {
        return [
            'opens_at' => 'immutable_datetime',
            'closes_at' => 'immutable_datetime',
            'is_published' => 'boolean',
            'should_release_decisions_immediately' => 'boolean',
            'offer_response_days' => 'integer',
        ];
    }

    /**
     * @return HasMany<AdmissionCycleProgram, $this>
     */
    public function programs(): HasMany
    {
        return $this->hasMany(AdmissionCycleProgram::class);
    }

    /**
     * @return HasMany<AdmissionCycleDocumentType, $this>
     */
    public function documentTypes(): HasMany
    {
        return $this->hasMany(AdmissionCycleDocumentType::class);
    }

    /**
     * @return HasMany<Application, $this>
     */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    /**
     * Published cycles taking applications at the time.
     *
     * @param  Builder<self>  $query
     */
    public function scopeAcceptingApplications(Builder $query, ?CarbonImmutable $at = null): void
    {
        $at ??= CarbonImmutable::now();

        $query->where('is_published', true)
            ->where('opens_at', '<=', $at)
            ->where(fn (Builder $query) => $query->whereNull('closes_at')->orWhere('closes_at', '>', $at));
    }

    public function isAcceptingApplications(?CarbonImmutable $at = null): bool
    {
        $at ??= CarbonImmutable::now();

        return $this->is_published && $this->opens_at->lessThanOrEqualTo($at) && ($this->closes_at === null || $this->closes_at->greaterThan($at));
    }

    public function isRolling(): bool
    {
        return $this->closes_at === null;
    }
}
