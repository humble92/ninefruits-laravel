<?php

namespace App\Models\Term;

use App\Domain\Term\Enums\TermStatus;
use App\Models\Concerns\HasTranslatableAttributes;
use App\Models\Contracts\Translatable;
use Carbon\CarbonImmutable;
use Database\Factories\Term\TermFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
