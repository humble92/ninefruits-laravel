<?php

namespace App\Models\Term;

use App\Models\Concerns\HasTranslatableAttributes;
use App\Models\Contracts\Translatable;
use Carbon\CarbonImmutable;
use Database\Factories\Term\AcademicYearFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property CarbonImmutable $start_date
 * @property CarbonImmutable $end_date
 * @property-read Collection<int, Term> $terms
 */
class AcademicYear extends Model implements Translatable
{
    /** @use HasFactory<AcademicYearFactory> */
    use HasFactory, HasTranslatableAttributes;

    /** @var list<string> */
    public array $translatable = ['name'];

    protected $fillable = ['code', 'name', 'start_date', 'end_date'];

    protected function casts(): array
    {
        return ['start_date' => 'immutable_date', 'end_date' => 'immutable_date'];
    }

    /**
     * @return HasMany<Term, $this>
     */
    public function terms(): HasMany
    {
        return $this->hasMany(Term::class)->orderBy('start_date');
    }
}
