<?php

namespace App\Models;

use App\Domain\Security\Enums\Permission;
use App\Models\Person\Person;
use App\Models\Security\RoleAssignment;
use App\Models\Security\UserIdentity;
use Carbon\CarbonImmutable;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * Login account. One per person for life (docs/porting-plan.md section 10.1).
 *
 * @property int $id
 * @property string|null $name Display name for accounts without a person
 * @property int|null $person_id
 * @property int $failed_login_count
 * @property CarbonImmutable|null $locked_until
 * @property string $email
 * @property CarbonImmutable|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property CarbonImmutable|null $two_factor_confirmed_at
 * @property CarbonImmutable|null $email_mfa_enabled_at
 * @property CarbonImmutable|null $mfa_grace_started_at
 * @property string|null $locale
 * @property bool $is_active
 * @property CarbonImmutable|null $last_login_at
 */
#[Fillable(['name', 'email', 'password', 'locale'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]
class User extends Authenticatable implements FilamentUser, HasLocalePreference, HasName
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * Defaults so that strict mode knows these attributes on newly created models.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
        'locale' => null,
        'two_factor_secret' => null,
        'two_factor_recovery_codes' => null,
        'two_factor_confirmed_at' => null,
        'email_mfa_enabled_at' => null,
        'mfa_grace_started_at' => null,
        'last_login_at' => null,
        'name' => null,
        'person_id' => null,
        'failed_login_count' => 0,
        'locked_until' => null,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'immutable_datetime',
            'two_factor_confirmed_at' => 'immutable_datetime',
            'email_mfa_enabled_at' => 'immutable_datetime',
            'mfa_grace_started_at' => 'immutable_datetime',
            'last_login_at' => 'immutable_datetime',
            'locked_until' => 'immutable_datetime',
            'failed_login_count' => 'integer',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    /**
     * @return HasMany<RoleAssignment, $this>
     */
    public function roleAssignments(): HasMany
    {
        return $this->hasMany(RoleAssignment::class);
    }

    /**
     * @return BelongsTo<Person, $this>
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    /**
     * @return HasMany<UserIdentity, $this>
     */
    public function identities(): HasMany
    {
        return $this->hasMany(UserIdentity::class);
    }

    /**
     * The person's name when linked, otherwise the account's own name.
     */
    public function displayName(): string
    {
        if ($this->person_id !== null) {
            $person = $this->relationLoaded('person') ? $this->person : $this->person()->first();

            if ($person !== null) {
                return $person->displayName();
            }
        }

        return (string) ($this->name ?? $this->email);
    }

    public function isLocked(): bool
    {
        return $this->locked_until !== null && $this->locked_until->isFuture();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active && $this->can(Permission::AdminPanelAccess->value);
    }

    public function getFilamentName(): string
    {
        return $this->displayName();
    }

    public function preferredLocale(): ?string
    {
        return $this->locale;
    }
}
