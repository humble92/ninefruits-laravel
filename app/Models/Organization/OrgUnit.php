<?php

namespace App\Models\Organization;

use App\Models\Concerns\HasEffectivePeriod;
use App\Models\Concerns\HasTranslatableAttributes;
use App\Models\Contracts\Translatable;
use Carbon\CarbonImmutable;
use Database\Factories\Organization\OrgUnitFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * University, college, department, major or office, valid for a period. Reorganizations
 * end the unit and link successors (OrgUnitSuccession) instead of editing history.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $short_name
 * @property int $org_unit_type_id
 * @property int|null $parent_id
 * @property CarbonImmutable $effective_start_date
 * @property CarbonImmutable|null $effective_end_date
 * @property int $sort_order
 * @property-read OrgUnitType $type
 * @property-read OrgUnit|null $parent
 */
class OrgUnit extends Model implements Translatable
{
    /** @use HasFactory<OrgUnitFactory> */
    use HasEffectivePeriod, HasFactory, HasTranslatableAttributes;

    /** @var list<string> */
    public array $translatable = ['name', 'short_name'];

    protected $fillable = ['code', 'name', 'short_name', 'org_unit_type_id', 'parent_id', 'effective_start_date', 'effective_end_date', 'sort_order'];

    protected $attributes = ['short_name' => null, 'parent_id' => null, 'effective_end_date' => null, 'sort_order' => 0];

    protected function casts(): array
    {
        return [
            'effective_start_date' => 'immutable_date',
            'effective_end_date' => 'immutable_date',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<OrgUnitType, $this>
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(OrgUnitType::class, 'org_unit_type_id');
    }

    /**
     * @return BelongsTo<OrgUnit, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<OrgUnit, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * @return HasMany<OrgUnitSuccession, $this>
     */
    public function successions(): HasMany
    {
        return $this->hasMany(OrgUnitSuccession::class, 'predecessor_org_unit_id');
    }

    /**
     * @return HasMany<OrgUnitSuccession, $this>
     */
    public function predecessions(): HasMany
    {
        return $this->hasMany(OrgUnitSuccession::class, 'successor_org_unit_id');
    }

    /**
     * @param  list<int>  $ids
     * @return list<int>
     */
    public static function descendantIdsOf(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $result = DB::scalar('SELECT util.org_unit_descendant_ids(?::bigint[])', ['{'.implode(',', array_map('intval', $ids)).'}']);

        return self::parseIdArray($result);
    }

    /**
     * Ids from this unit up to the root, nearest first.
     *
     * @return list<int>
     */
    public function ancestorIds(): array
    {
        return self::parseIdArray(DB::scalar('SELECT util.org_unit_ancestor_ids(?)', [$this->id]));
    }

    /**
     * @return list<int>
     */
    private static function parseIdArray(mixed $value): array
    {
        $value = trim((string) $value, '{}');

        return $value === '' ? [] : array_map('intval', explode(',', $value));
    }
}
