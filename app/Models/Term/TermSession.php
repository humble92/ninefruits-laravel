<?php

namespace App\Models\Term;

use App\Models\Concerns\HasTranslatableAttributes;
use App\Models\Contracts\Translatable;
use Carbon\CarbonImmutable;
use Database\Factories\Term\TermSessionFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A part of a term in which sections meet: the full term, a first or second half, an
 * intensive block. Course deadlines (add/drop, withdrawal, grading, evaluation) are the
 * session's windows, so every section follows its session.
 *
 * @property int $id
 * @property int $term_id
 * @property string $code
 * @property string $name
 * @property CarbonImmutable $start_date
 * @property CarbonImmutable $end_date
 * @property bool $is_full_term
 * @property int $sort_order
 * @property-read Term $term
 * @property-read Collection<int, TermWindow> $windows
 */
class TermSession extends Model implements Translatable
{
    /** @use HasFactory<TermSessionFactory> */
    use HasFactory, HasTranslatableAttributes;

    public const string FULL_TERM_CODE = 'FULL';

    /** @var list<string> */
    public array $translatable = ['name'];

    protected $fillable = ['term_id', 'code', 'name', 'start_date', 'end_date', 'is_full_term', 'sort_order'];

    protected $attributes = ['is_full_term' => false, 'sort_order' => 0];

    protected function casts(): array
    {
        return ['start_date' => 'immutable_date', 'end_date' => 'immutable_date', 'is_full_term' => 'boolean', 'sort_order' => 'integer'];
    }

    /**
     * Name of the automatic full-term session in every language with a translation.
     *
     * @return array<string, string>
     */
    public static function fullTermName(): array
    {
        $names = [];

        foreach (glob(lang_path('*'), GLOB_ONLYDIR) ?: [] as $directory) {
            $locale = basename($directory);
            $name = trans('reference.term_sessions.full_term', [], $locale);

            if ($name !== 'reference.term_sessions.full_term') {
                $names[$locale] = $name;
            }
        }

        return $names === [] ? ['en' => 'Full term'] : $names;
    }

    /**
     * @return BelongsTo<Term, $this>
     */
    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    /**
     * @return HasMany<TermWindow, $this>
     */
    public function windows(): HasMany
    {
        return $this->hasMany(TermWindow::class)->orderBy('opens_at');
    }
}
