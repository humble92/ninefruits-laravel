<?php

namespace App\Models\Organization;

use App\Domain\Organization\Enums\OrgUnitChangeType;
use Carbon\CarbonImmutable;
use Database\Factories\Organization\OrgUnitSuccessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $predecessor_org_unit_id
 * @property int $successor_org_unit_id
 * @property OrgUnitChangeType $change_type
 * @property CarbonImmutable $effective_date
 * @property string|null $reason
 * @property-read OrgUnit $predecessor
 * @property-read OrgUnit $successor
 */
class OrgUnitSuccession extends Model
{
    /** @use HasFactory<OrgUnitSuccessionFactory> */
    use HasFactory;

    protected $fillable = ['predecessor_org_unit_id', 'successor_org_unit_id', 'change_type', 'effective_date', 'reason'];

    protected $attributes = ['reason' => null];

    protected function casts(): array
    {
        return ['change_type' => OrgUnitChangeType::class, 'effective_date' => 'immutable_date'];
    }

    /**
     * @return BelongsTo<OrgUnit, $this>
     */
    public function predecessor(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class, 'predecessor_org_unit_id');
    }

    /**
     * @return BelongsTo<OrgUnit, $this>
     */
    public function successor(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class, 'successor_org_unit_id');
    }
}
