<?php

namespace Database\Factories;

use App\Domain\Security\Actions\AssignRole;
use App\Domain\Security\Enums\Permission;
use App\Domain\Security\Enums\ScopeType;
use App\Models\Security\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Confirmed two-factor authentication with the secret in TWO_FACTOR_TEST_SECRET.
     */
    public function withMfa(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_secret' => encrypt(self::TWO_FACTOR_TEST_SECRET),
            'two_factor_recovery_codes' => encrypt((string) json_encode(['recovery-code-1', 'recovery-code-2'])),
            'two_factor_confirmed_at' => now(),
        ]);
    }

    public const string TWO_FACTOR_TEST_SECRET = 'JBSWY3DPEHPK3PXP';

    /**
     * Assigns a role (global scope) that holds the given permissions.
     *
     * @param  list<Permission>  $permissions
     */
    public function withPermissions(array $permissions, ScopeType $scopeType = ScopeType::Global, ?int $scopeId = null): static
    {
        return $this->afterCreating(function (User $user) use ($permissions, $scopeType, $scopeId) {
            $role = Role::factory()->withPermissions($permissions)->create();

            app(AssignRole::class)->handle($user, $role, 'test', $scopeType, $scopeId);
        });
    }
}
