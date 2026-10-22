<?php

use App\Domain\Security\Actions\ResetUserMfa;
use App\Domain\Security\Enums\MfaEnforcement;
use App\Domain\Security\Enums\SecurityEventType;
use App\Domain\Security\Notifications\TwoFactorEmailCodeNotification;
use App\Domain\Security\Services\EmailCodeChallenge;
use App\Livewire\Portal\SecuritySettings;
use App\Models\Security\SecurityEvent;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;

beforeEach(function () {
    Notification::fake();
    setSecuritySettings(['mfa_enforcement' => MfaEnforcement::Recommended, 'mfa_email_code_enabled' => true]);
});

function lastEmailCode(User $user): string
{
    $code = null;

    Notification::assertSentTo($user, TwoFactorEmailCodeNotification::class, function (TwoFactorEmailCodeNotification $notification) use (&$code) {
        $code = $notification->code;

        return true;
    });

    return (string) $code;
}

function signInWithPassword(User $user): TestResponse
{
    return test()->post('/login', ['email' => $user->email, 'password' => 'password']);
}

it('sends a code and asks for it when email is the only method', function () {
    $user = User::factory()->create(['email_mfa_enabled_at' => now()]);

    signInWithPassword($user)->assertRedirect('/two-factor-challenge');
    $this->assertGuest();

    $this->get('/two-factor-challenge')->assertOk()->assertSee(__('authentication.two_factor.email_instructions'));

    $this->post('/two-factor-challenge/email', ['email_code' => lastEmailCode($user)])->assertRedirect('/portal');

    $this->assertAuthenticatedAs($user);
    expect(SecurityEvent::query()->where('type', SecurityEventType::MfaEmailCodeSent)->exists())->toBeTrue();
});

it('rejects a wrong code and records the failure', function () {
    $user = User::factory()->create(['email_mfa_enabled_at' => now()]);
    signInWithPassword($user);

    $this->post('/two-factor-challenge/email', ['email_code' => '000000'])->assertSessionHasErrors('email_code');

    $this->assertGuest();
    expect(SecurityEvent::query()->where('type', SecurityEventType::MfaChallengeFailed)->where('user_id', $user->id)->exists())->toBeTrue();
});

it('discards the code after too many wrong attempts', function () {
    $user = User::factory()->create(['email_mfa_enabled_at' => now()]);
    $challenge = app(EmailCodeChallenge::class);
    $challenge->send($user, EmailCodeChallenge::PURPOSE_LOGIN);
    $code = lastEmailCode($user);

    foreach (range(1, EmailCodeChallenge::MAX_ATTEMPTS) as $attempt) {
        expect($challenge->verify($user, EmailCodeChallenge::PURPOSE_LOGIN, '999999'))->toBeFalse();
    }

    expect($challenge->verify($user, EmailCodeChallenge::PURPOSE_LOGIN, $code))->toBeFalse();
});

it('accepts a code once and only before it expires', function () {
    $user = User::factory()->create();
    $challenge = app(EmailCodeChallenge::class);

    $challenge->send($user, EmailCodeChallenge::PURPOSE_LOGIN);
    $code = lastEmailCode($user);

    expect($challenge->verify($user, EmailCodeChallenge::PURPOSE_LOGIN, $code))->toBeTrue()
        ->and($challenge->verify($user, EmailCodeChallenge::PURPOSE_LOGIN, $code))->toBeFalse();

    $this->travel(2)->minutes();
    $challenge->send($user, EmailCodeChallenge::PURPOSE_LOGIN);
    $second = null;
    Notification::assertSentTo($user, TwoFactorEmailCodeNotification::class, function ($notification) use (&$second) {
        $second = $notification->code;

        return true;
    });

    $this->travel(EmailCodeChallenge::CODE_LIFETIME_MINUTES + 1)->minutes();

    expect($challenge->verify($user, EmailCodeChallenge::PURPOSE_LOGIN, (string) $second))->toBeFalse();
});

it('does not accept a code issued for another purpose', function () {
    $user = User::factory()->create();
    $challenge = app(EmailCodeChallenge::class);
    $challenge->send($user, EmailCodeChallenge::PURPOSE_ENROLLMENT);

    expect($challenge->verify($user, EmailCodeChallenge::PURPOSE_LOGIN, lastEmailCode($user)))->toBeFalse();
});

it('limits how often codes are sent', function () {
    $user = User::factory()->create();
    $challenge = app(EmailCodeChallenge::class);

    expect($challenge->send($user, EmailCodeChallenge::PURPOSE_LOGIN))->toBeTrue()
        ->and($challenge->send($user, EmailCodeChallenge::PURPOSE_LOGIN))->toBeFalse();

    foreach (range(2, EmailCodeChallenge::SENDS_PER_HOUR) as $send) {
        $this->travel(EmailCodeChallenge::RESEND_AFTER_SECONDS + 1)->seconds();
        expect($challenge->send($user, EmailCodeChallenge::PURPOSE_LOGIN))->toBeTrue();
    }

    $this->travel(EmailCodeChallenge::RESEND_AFTER_SECONDS + 1)->seconds();
    expect($challenge->send($user, EmailCodeChallenge::PURPOSE_LOGIN))->toBeFalse();
});

it('does not offer email codes to users who only use an authenticator app', function () {
    $user = User::factory()->withMfa()->create();
    signInWithPassword($user)->assertRedirect('/two-factor-challenge');

    $this->post('/two-factor-challenge/email/send')->assertForbidden();
    Notification::assertNothingSent();
});

it('stops counting email codes when the institution turns them off', function () {
    $user = User::factory()->create(['email_mfa_enabled_at' => now()]);
    setSecuritySettings(['mfa_email_code_enabled' => false]);

    signInWithPassword($user)->assertRedirect('/portal');
    Notification::assertNothingSent();
});

it('satisfies a required policy with email codes', function () {
    setSecuritySettings(['mfa_enforcement' => MfaEnforcement::RequiredForAll, 'mfa_grace_period_days' => 0]);

    $this->actingAs(User::factory()->create(['email_mfa_enabled_at' => now()]))->get('/portal')->assertOk();
});

it('enrolls email codes from the security page after password confirmation', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)->test(SecuritySettings::class)
        ->call('sendEmailCode')
        ->assertRedirect(route('password.confirm'));

    session(['auth.password_confirmed_at' => time()]);

    $component = Livewire::actingAs($user)->test(SecuritySettings::class)
        ->call('sendEmailCode')
        ->assertHasNoErrors()
        ->assertNoRedirect()
        ->assertSet('emailCodeSent', true);

    $component->set('emailCode', lastEmailCode($user))->call('confirmEmail')->assertHasNoErrors();

    expect($user->fresh()->email_mfa_enabled_at)->not->toBeNull()
        ->and(SecurityEvent::query()->where('type', SecurityEventType::MfaEmailEnabled)->exists())->toBeTrue();
});

it('keeps at least one method when two-factor authentication is required', function () {
    setSecuritySettings(['mfa_enforcement' => MfaEnforcement::RequiredForAll]);
    session(['auth.password_confirmed_at' => time()]);
    $user = User::factory()->withMfa()->create(['email_mfa_enabled_at' => now()]);

    Livewire::actingAs($user)->test(SecuritySettings::class)->call('disableEmail')->assertHasNoErrors();
    expect($user->fresh()->email_mfa_enabled_at)->toBeNull();

    Livewire::actingAs($user->fresh())->test(SecuritySettings::class)->call('disable')->assertHasErrors('disable');
    expect($user->fresh()->two_factor_confirmed_at)->not->toBeNull();
});

it('is cleared by an administrator reset', function () {
    $user = User::factory()->create(['email_mfa_enabled_at' => now()]);

    app(ResetUserMfa::class)->handle($user, 'Lost mailbox access', User::factory()->create());

    expect($user->fresh()->email_mfa_enabled_at)->toBeNull();
});

it('changes nothing until the password is confirmed', function (string $action) {
    $user = User::factory()->withMfa()->create();

    Livewire::actingAs($user)->test(SecuritySettings::class)
        ->call($action)
        ->assertRedirect(route('password.confirm'));

    expect($user->fresh())
        ->two_factor_confirmed_at->not->toBeNull()
        ->email_mfa_enabled_at->toBeNull();
    Notification::assertNothingSent();
})->with(['enable', 'disable', 'regenerateRecoveryCodes', 'sendEmailCode', 'disableEmail']);
