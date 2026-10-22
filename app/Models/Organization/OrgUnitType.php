<?php

namespace App\Models\Organization;

use App\Models\Concerns\HasTranslatableAttributes;
use App\Models\Contracts\Translatable;
use Database\Factories\Organization\OrgUnitTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * E.g. university, college, department, major, administrative office.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property bool $is_academic
 * @property int $sort_order
 */
class OrgUnitType extends Model implements Translatable
{
    /** @use HasFactory<OrgUnitTypeFactory> */
    use HasFactory, HasTranslatableAttributes;

    /** @var list<string> */
    public array $translatable = ['name'];

    protected $fillable = ['code', 'name', 'is_academic', 'sort_order'];

    protected $attributes = ['is_academic' => true, 'sort_order' => 0];

    protected function casts(): array
    {
        return ['is_academic' => 'boolean', 'sort_order' => 'integer'];
    }
}
