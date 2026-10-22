<?php

namespace App\Models\Term;

use App\Domain\Term\Enums\TermStatus;
use App\Models\Concerns\HasTranslatableAttributes;
use App\Models\Contracts\Translatable;
use App\Models\Offering\Section;
use App\Models\Registration\RegistrationRuleSet;
use Carbon\CarbonImmutable;
use Database\Factories\Term\TermFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Validation\ValidationException;

/**
 * A term (semester, quarter, session). The code (e.g. 2026-FA) is for display only and
 * carries no business meaning.
 *
 * @property int $id
 * @property int $academic_year_id
 * @property int $term_type_id
 * @property string $code
 * @property string $name
 * @property CarbonImmutable $start_date
 * @property CarbonImmutable $end_date
 * @property TermStatus $status
 * @property-read AcademicYear $academicYear
 * @property-read TermType $type
 * @property-read Collection<int, TermWindow> $windows
 * @property-read Collection<int, TermSession> $sessions
 */
class Term extends Model implements Translatable
{
    /** @use HasFactory<TermFactory> */
    use HasFactory, HasTranslatableAttributes;

    /** @var list<string> */
    public array $translatable = ['name'];

    protected $fillable = ['academic_year_id', 'term_type_id', 'code', 'name', 'start_date', 'end_date'];

    protected $attributes = ['status' => 'planned'];

    protected function casts(): array
    {
        return ['start_date' => 'immutable_date', 'end_date' => 'immutable_date', 'status' => TermStatus::class];
    }

    /**
     * Every term has a full-term session with the term's dates; it follows date changes.
     * Shorter sessions must stay inside the term.
     */
    protected static function booted(): void
    {
        static::created(function (self $term): void {
            TermSession::query()->create([
                'term_id' => $term->id,
                'code' => TermSession::FULL_TERM_CODE,
                'name' => TermSession::fullTermName(),
                'start_date' => $term->start_date,
                'end_date' => $term->end_date,
                'is_full_term' => true,
            ]);
        });

        static::updating(function (self $term): void {
            if (! $term->isDirty(['start_date', 'end_date'])) {
                return;
            }

            $outside = TermSession::query()->where('term_id', $term->id)->where('is_full_term', false)
                ->where(fn ($query) => $query->where('start_date', '<', $term->start_date)->orWhere('end_date', '>', $term->end_date))
                ->exists();

            if ($outside) {
                throw ValidationException::withMessages(['start_date' => __('reference.errors.sessions_outside_term')]);
            }
        });

        static::updated(function (self $term): void {
            if ($term->wasChanged(['start_date', 'end_date'])) {
                TermSession::query()->where('term_id', $term->id)->where('is_full_term', true)
                    ->update(['start_date' => $term->start_date, 'end_date' => $term->end_date]);
            }
        });
    }

    /**
     * @return HasMany<TermSession, $this>
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(TermSession::class)->orderByDesc('is_full_term')->orderBy('start_date')->orderBy('sort_order');
    }

    /**
     * @return HasMany<Section, $this>
     */
    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    /**
     * @return HasOne<RegistrationRuleSet, $this>
     */
    public function registrationRuleSet(): HasOne
    {
        return $this->hasOne(RegistrationRuleSet::class);
    }

    public function fullTermSession(): TermSession
    {
        return TermSession::query()->where('term_id', $this->id)->where('is_full_term', true)->sole();
    }

    /**
     * @return BelongsTo<AcademicYear, $this>
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * @return BelongsTo<TermType, $this>
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(TermType::class, 'term_type_id');
    }

    /**
     * @return HasMany<TermWindow, $this>
     */
    public function windows(): HasMany
    {
        return $this->hasMany(TermWindow::class)->orderBy('opens_at');
    }
}
