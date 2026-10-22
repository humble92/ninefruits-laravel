<?php

use App\Domain\Person\Enums\StudentStatus;
use App\Domain\Security\Enums\MfaEnforcement;
use App\Domain\Security\Enums\SsoLinkMode;
use App\Domain\Security\Enums\StudentNumberLogin;
use App\Domain\Security\Settings\AuthenticationSettings;
use App\Models\Person\StudentRecord;
use App\Models\Security\UserIdentity;
use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;

function authenticationSettings(array $values): void
{
    $settings = app(AuthenticationSettings::class);
    $settings->fill($values)->save();
}

beforeEach(function () {
    setSecuritySettings(['mfa_enforcement' => MfaEnforcement::Recommended]);
    $this->record = StudentRecord::factory()->create(['student_number' => '202612345']);
    $this->user = User::factory()->create(['person_id' => $this->record->person_id, 'email' => 'minsu@example.test']);
});

function signIn(string $identifier, string $password = 'password'): TestResponse
{
    return test()->post('/login', ['email' => $identifier, 'password' => $password]);
}

it('signs in with a student number according to the setting', function (StudentNumberLogin $setting, StudentStatus $status, bool $allowed) {
    authenticationSettings(['student_number_login' => $setting]);
    $this->record->forceFill(['status' => $status, 'graduation_date' => $status === StudentStatus::Graduated ? '2026-02-20' : null])->save();

    signIn('202612345');

    $allowed ? $this->assertAuthenticatedAs($this->user) : $this->assertGuest();
})->with([
    'disabled' => [StudentNumberLogin::Disabled, StudentStatus::Enrolled, false],
    'active records, enrolled' => [StudentNumberLogin::ActiveRecords, StudentStatus::Enrolled, true],
    'active records, on leave' => [StudentNumberLogin::ActiveRecords, StudentStatus::OnLeave, true],
    'active records, graduated' => [StudentNumberLogin::ActiveRecords, StudentStatus::Graduated, false],
    'all records, graduated' => [StudentNumberLogin::AllRecords, StudentStatus::Graduated, true],
]);

it('always accepts the personal email, and gives the same error for every failure', function () {
    authenticationSettings(['student_number_login' => StudentNumberLogin::ActiveRecords]);

    $wrongPassword = signIn('202612345', 'wrong')->assertSessionHasErrors('email');
    $unknown = signIn('999999999')->assertSessionHasErrors('email');

    expect(session('errors')->get('email'))->toBe([__('auth.failed')]);

    signIn('minsu@example.test');
    $this->assertAuthenticatedAs($this->user);
});

it('locks the account after repeated failures, counted across email and student number', function () {
    authenticationSettings(['student_number_login' => StudentNumberLogin::ActiveRecords, 'lockout_threshold' => 4, 'lockout_minutes' => 15]);

    foreach (['202612345', 'minsu@example.test', '202612345', 'minsu@example.test'] as $identifier) {
        app('Illuminate\Cache\RateLimiter')->clear('user:'.$this->user->id.'|127.0.0.1');
        signIn($identifier, 'wrong');
    }

    expect($this->user->fresh()->isLocked())->toBeTrue();

    signIn('minsu@example.test');
    $this->assertGuest();

    $this->travel(16)->minutes();
    signIn('minsu@example.test');
    $this->assertAuthenticatedAs($this->user);
    expect($this->user->fresh()->locked_until)->toBeNull();
});

/*
| SSO with a fake OIDC provider. Keys are generated per test run.
*/

function fakeOidcProvider(): array
{
    $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    openssl_pkey_export($key, $privatePem);
    $details = openssl_pkey_get_details($key);
    $b64 = fn (string $value) => rtrim(strtr(base64_encode($value), '+/', '-_'), '=');

    Http::fake([
        'https://idp.example.test/.well-known/openid-configuration' => Http::response([
            'issuer' => 'https://idp.example.test',
            'authorization_endpoint' => 'https://idp.example.test/authorize',
            'token_endpoint' => 'https://idp.example.test/token',
            'jwks_uri' => 'https://idp.example.test/jwks',
        ]),
        'https://idp.example.test/jwks' => Http::response(['keys' => [[
            'kty' => 'RSA', 'alg' => 'RS256', 'use' => 'sig', 'kid' => 'test-key',
            'n' => $b64($details['rsa']['n']), 'e' => $b64($details['rsa']['e']),
        ]]]),
        'https://idp.example.test/token' => fn ($request) => Http::response(['id_token' => test()->nextIdToken]),
    ]);

    authenticationSettings(['sso_providers' => [[
        'key' => 'school', 'label' => 'School account', 'issuer' => 'https://idp.example.test',
        'client_id' => 'sis-client', 'client_secret' => Crypt::encryptString('secret'), 'scopes' => 'openid email',
        'trusts_mfa' => false, 'is_enabled' => true,
    ]]]);

    return ['private_key' => $privatePem];
}

/**
 * Runs the browser part of the flow and returns the callback response.
 */
function ssoSignIn(array $provider, array $claims): TestResponse
{
    $redirect = test()->get('/auth/sso/school/redirect')->assertRedirect();
    parse_str((string) parse_url($redirect->headers->get('Location'), PHP_URL_QUERY), $query);

    test()->nextIdToken = JWT::encode(array_merge([
        'iss' => 'https://idp.example.test', 'aud' => 'sis-client', 'iat' => time(), 'exp' => time() + 300,
        'nonce' => $query['nonce'],
    ], $claims), $provider['private_key'], 'RS256', 'test-key');

    expect($query['code_challenge_method'])->toBe('S256');

    return test()->get('/auth/sso/school/callback?'.http_build_query(['state' => $query['state'], 'code' => 'abc']));
}

it('signs in with a linked school account', function () {
    $provider = fakeOidcProvider();
    UserIdentity::query()->create(['user_id' => $this->user->id, 'provider' => 'school', 'subject' => 'sub-123', 'linked_at' => now()]);

    ssoSignIn($provider, ['sub' => 'sub-123'])->assertRedirect('/portal');

    $this->assertAuthenticatedAs($this->user);
});

it('rejects tokens with a wrong audience or nonce', function (array $claims) {
    $provider = fakeOidcProvider();
    UserIdentity::query()->create(['user_id' => $this->user->id, 'provider' => 'school', 'subject' => 'sub-123', 'linked_at' => now()]);

    ssoSignIn($provider, ['sub' => 'sub-123', ...$claims])->assertRedirect('/login');

    $this->assertGuest();
})->with([
    'audience' => [['aud' => 'another-client']],
    'nonce' => [['nonce' => 'replayed']],
    'issuer' => [['iss' => 'https://evil.example.test']],
]);

it('links on first sign-in by institutional email when configured, otherwise asks to link manually', function () {
    $provider = fakeOidcProvider();
    $this->record->forceFill(['institutional_email' => 'minsu@school.example.test'])->save();

    ssoSignIn($provider, ['sub' => 'sub-new', 'email' => 'minsu@school.example.test', 'email_verified' => true])
        ->assertRedirect('/login');
    $this->assertGuest();

    authenticationSettings(['sso_link_mode' => SsoLinkMode::InstitutionalEmail]);

    ssoSignIn($provider, ['sub' => 'sub-new', 'email' => 'minsu@school.example.test', 'email_verified' => true])
        ->assertRedirect('/portal');

    $this->assertAuthenticatedAs($this->user);
    expect(UserIdentity::query()->where('subject', 'sub-new')->sole()->user_id)->toBe($this->user->id);
});

it('still asks enrolled users for their second factor after SSO', function () {
    $provider = fakeOidcProvider();
    $user = User::factory()->withMfa()->create();
    UserIdentity::query()->create(['user_id' => $user->id, 'provider' => 'school', 'subject' => 'sub-mfa', 'linked_at' => now()]);

    ssoSignIn($provider, ['sub' => 'sub-mfa'])->assertRedirect(route('two-factor.login'));
    $this->assertGuest();

    $this->post('/two-factor-challenge', ['code' => currentOtp()])->assertRedirect('/portal');
    $this->assertAuthenticatedAs($user);
});

it('lets an SSO-linked graduate sign in with the personal email', function () {
    UserIdentity::query()->create(['user_id' => $this->user->id, 'provider' => 'school', 'subject' => 'sub-123', 'linked_at' => now()]);
    $this->record->forceFill(['status' => StudentStatus::Graduated, 'graduation_date' => '2026-02-20'])->save();

    signIn('minsu@example.test');
    $this->assertAuthenticatedAs($this->user);

    auth()->logout();
    authenticationSettings(['password_login_for_sso_users' => false]);

    signIn('minsu@example.test');
    $this->assertGuest();
});
