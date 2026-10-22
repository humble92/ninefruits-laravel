<?php

namespace App\Models\Term;

use App\Models\Concerns\HasTranslatableAttributes;
use App\Models\Contracts\Translatable;
use Database\Factories\Term\TermTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * E.g. spring, summer, fall, winter. Regular terms and intensive terms have different
 * rules later (credit limits).
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property bool $is_regular
 * @property int $sort_order
 */
class TermType extends Model implements Translatable
{
    /** @use HasFactory<TermTypeFactory> */
    use HasFactory, HasTranslatableAttributes;

    /** @var list<string> */
    public array $translatable = ['name'];

    protected $fillable = ['code', 'name', 'is_regular', 'sort_order'];

    protected $attributes = ['is_regular' => true, 'sort_order' => 0];

    protected function casts(): array
    {
        return ['is_regular' => 'boolean', 'sort_order' => 'integer'];
    }
}
