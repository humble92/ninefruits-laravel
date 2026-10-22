<?php

use App\Domain\Security\Enums\SecurityEventType;
use App\Domain\Security\Services\TrustedDevices;
use App\Models\Security\SecurityEvent;
use App\Models\User;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Support\Facades\DB;

it('signs in with valid credentials and records the event', function () {
    $user = User::factory()->create();

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect('/portal');

    $this->assertAuthenticatedAs($user);

    expect(SecurityEvent::query()->where('type', SecurityEventType::LoginSucceeded)->where('user_id', $user->id)->exists())->toBeTrue()
        ->and($user->fresh()->last_login_at)->not->toBeNull();
});

it('attributes changes made during sign-in to the user', function () {
    $user = User::factory()->create();

    $this->post('/login', ['email' => $user->email, 'password' => 'password']);

    $change = DB::table('audit.record_changes')
        ->where('table_name', 'users')->where('operation', 'UPDATE')->latest('id')->first();

    expect(json_decode($change->changed_columns))->toContain('last_login_at')
        ->and($change->actor)->toBe("user:{$user->id}");
});

it('rejects invalid credentials and records the failure', function () {
    $user = User::factory()->create();

    $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();

    expect(SecurityEvent::query()->where('type', SecurityEventType::LoginFailed)->value('identifier'))->toBe($user->email);
});

it('does not let deactivated accounts sign in', function () {
    $user = User::factory()->inactive()->create();

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('asks enrolled users for a two-factor code', function () {
    $user = User::factory()->withMfa()->create();

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect('/two-factor-challenge');

    $this->assertGuest();

    $this->post('/two-factor-challenge', ['code' => currentOtp()])
        ->assertRedirect('/portal');

    $this->assertAuthenticatedAs($user);
});

it('remembers a device after a successful challenge when asked', function () {
    setSecuritySettings(['mfa_remember_device_days' => 30]);
    $user = User::factory()->withMfa()->create();

    $this->post('/login', ['email' => $user->email, 'password' => 'password']);
    $response = $this->post('/two-factor-challenge', ['code' => currentOtp(), 'remember_device' => '1']);

    $response->assertCookie(TrustedDevices::COOKIE);
    $cookie = collect($response->headers->getCookies())->firstWhere(fn ($cookie) => $cookie->getName() === TrustedDevices::COOKIE);

    auth()->logout();

    // The response cookie is encrypted; the test client encrypts cookies itself.
    $plainValue = CookieValuePrefix::remove(decrypt($cookie->getValue(), false));

    $this->withCookie(TrustedDevices::COOKIE, $plainValue)
        ->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect('/portal');

    $this->assertAuthenticatedAs($user);
});

it('does not trust a remembered device after the two-factor enrollment changes', function () {
    setSecuritySettings(['mfa_remember_device_days' => 30]);
    $user = User::factory()->withMfa()->create();

    $request = request();
    $trusted = app(TrustedDevices::class);
    $trusted->remember($user);
    $cookie = collect(app('cookie')->getQueuedCookies())->first();
    $request->cookies->set(TrustedDevices::COOKIE, $cookie->getValue());

    expect($trusted->isTrusted($request, $user))->toBeTrue();

    $user->forceFill(['two_factor_confirmed_at' => now()->addMinute()])->save();

    expect($trusted->isTrusted($request, $user->fresh()))->toBeFalse();
});

it('ignores remembered devices when the policy turns the feature off', function () {
    setSecuritySettings(['mfa_remember_device_days' => 30]);
    $user = User::factory()->withMfa()->create();
    app(TrustedDevices::class)->remember($user);
    $cookie = collect(app('cookie')->getQueuedCookies())->first();

    setSecuritySettings(['mfa_remember_device_days' => 0]);

    $this->withCookie(TrustedDevices::COOKIE, $cookie->getValue())
        ->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect('/two-factor-challenge');
});
