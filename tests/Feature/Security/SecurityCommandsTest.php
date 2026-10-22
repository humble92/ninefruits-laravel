<?php

use App\Models\Person\Person;
use App\Models\Security\Activity;
use App\Models\Security\SecurityEvent;
use App\Models\User;
use Carbon\CarbonImmutable;

it('resets two-factor authentication only with a reason and an actor', function () {
    $admin = User::factory()->create(['email' => 'lead@example.test']);
    $user = User::factory()->withMfa()->create(['email' => 'lost-phone@example.test']);

    $this->artisan('security:reset-mfa', ['user' => $user->email])->assertFailed();
    $this->artisan('security:reset-mfa', ['user' => $user->email, '--reason' => 'Lost phone'])->assertFailed();
    expect($user->fresh()->two_factor_confirmed_at)->not->toBeNull();

    $this->artisan('security:reset-mfa', ['user' => $user->email, '--reason' => 'Lost phone', '--actor' => $admin->email])->assertSuccessful();

    expect($user->fresh()->two_factor_confirmed_at)->toBeNull()
        ->and(Activity::query()->where('event', 'mfa.reset')->value('reason'))->toBe('Lost phone');
});

it('unlocks a locked account', function () {
    $user = User::factory()->create(['email' => 'locked@example.test', 'locked_until' => CarbonImmutable::now()->addHour(), 'failed_login_count' => 3]);

    $this->artisan('security:unlock-user', ['user' => $user->email])->assertFailed();
    $this->artisan('security:unlock-user', ['user' => $user->email, '--reason' => 'Called the help desk'])->assertSuccessful();

    expect($user->fresh()->isLocked())->toBeFalse()
        ->and($user->fresh()->failed_login_count)->toBe(0)
        ->and(SecurityEvent::query()->where('type', 'account.unlocked')->exists())->toBeTrue();
});

it('lists likely duplicate people', function () {
    $this->artisan('people:find-duplicates')->assertSuccessful();

    Person::factory()->withNationalId('TEST-1')->count(2)->create();
    Person::factory()->count(2)->create(['family_name' => 'Kim', 'given_name' => 'Minsu', 'birth_date' => '2001-02-03']);

    $this->artisan('people:find-duplicates')
        ->expectsOutputToContain('national_id')
        ->expectsOutputToContain('name_birth_date')
        ->assertExitCode(2);
});
