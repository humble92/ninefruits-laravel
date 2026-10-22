<?php

namespace App\Models\Person;

use App\Domain\Person\Enums\GuardianRelationship;
use Database\Factories\Person\GuardianFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Guardian or family contact of a person (legacy SRFAMILY and the SR_PT* columns).
 *
 * @property int $id
 * @property int $person_id
 * @property GuardianRelationship $relationship
 * @property string $full_name
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $address
 * @property bool $is_primary_contact
 */
class Guardian extends Model
{
    /** @use HasFactory<GuardianFactory> */
    use HasFactory;

    protected $fillable = ['person_id', 'relationship', 'full_name', 'phone', 'email', 'address', 'is_primary_contact'];

    protected $attributes = ['phone' => null, 'email' => null, 'address' => null, 'is_primary_contact' => false];

    protected function casts(): array
    {
        return ['relationship' => GuardianRelationship::class, 'is_primary_contact' => 'boolean'];
    }

    /**
     * @return BelongsTo<Person, $this>
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
