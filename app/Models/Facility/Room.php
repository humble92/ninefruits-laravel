<?php

namespace App\Models\Facility;

use App\Models\Concerns\HasTranslatableAttributes;
use App\Models\Contracts\Translatable;
use App\Models\Institution\LookupValue;
use App\Models\Organization\OrgUnit;
use Database\Factories\Facility\RoomFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $building_id
 * @property string $code
 * @property string|null $name
 * @property int|null $room_type_id
 * @property int|null $org_unit_id
 * @property int|null $capacity
 * @property bool $is_schedulable
 * @property bool $is_active
 * @property string|null $note
 * @property-read Building $building
 * @property-read LookupValue|null $roomType
 * @property-read OrgUnit|null $orgUnit
 */
class Room extends Model implements Translatable
{
    /** @use HasFactory<RoomFactory> */
    use HasFactory, HasTranslatableAttributes;

    public const string ROOM_TYPE_LOOKUP = 'room_type';

    /** @var list<string> */
    public array $translatable = ['name'];

    protected $fillable = ['building_id', 'code', 'name', 'room_type_id', 'org_unit_id', 'capacity', 'is_schedulable', 'is_active', 'note'];

    protected $attributes = ['name' => null, 'room_type_id' => null, 'org_unit_id' => null, 'capacity' => null, 'is_schedulable' => true, 'is_active' => true, 'note' => null];

    protected function casts(): array
    {
        return ['capacity' => 'integer', 'is_schedulable' => 'boolean', 'is_active' => 'boolean'];
    }

    /**
     * @return BelongsTo<Building, $this>
     */
    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    /**
     * @return BelongsTo<LookupValue, $this>
     */
    public function roomType(): BelongsTo
    {
        return $this->belongsTo(LookupValue::class, 'room_type_id');
    }

    /**
     * @return BelongsTo<OrgUnit, $this>
     */
    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class);
    }

    /**
     * Display label such as "Science Hall 101".
     */
    public function label(): string
    {
        return trim($this->building->name.' '.$this->code);
    }
}
