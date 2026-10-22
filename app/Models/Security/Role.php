<?php

namespace App\Models\Security;

use App\Domain\Security\Enums\Persona;
use App\Models\Concerns\HasTranslatableAttributes;
use Database\Factories\Security\RoleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * @property int $id
 * @property string $name
 * @property string $guard_name
 * @property string $display_name
 * @property string|null $description
 * @property Persona|null $persona
 * @property bool $is_system
 */
class Role extends SpatieRole
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory, HasTranslatableAttributes;

    /** @var list<string> */
    public array $translatable = ['display_name', 'description'];

    protected $fillable = ['name', 'guard_name', 'display_name', 'description', 'persona', 'is_system'];

    protected function casts(): array
    {
        return [
            'persona' => Persona::class,
            'is_system' => 'boolean',
        ];
    }

    /**
     * @return HasMany<RoleAssignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(RoleAssignment::class);
    }

    public function isPersona(): bool
    {
        return $this->persona !== null;
    }

    /**
     * Roles that can be assigned by hand (persona roles are granted from data).
     *
     * @param  Builder<self>  $query
     */
    public function scopeAssignable(Builder $query): void
    {
        $query->whereNull('persona');
    }
}
