<?php

namespace App\Domain\Security\Services\Sso;

use Illuminate\Support\Facades\Crypt;

/**
 * One school sign-in provider from AuthenticationSettings::$sso_providers.
 */
final readonly class OidcProvider
{
    /**
     * @param  list<string>  $scopes
     */
    public function __construct(
        public string $key,
        public string $label,
        public string $issuer,
        public string $clientId,
        public string $clientSecret,
        public array $scopes = ['openid', 'email', 'profile'],
        public bool $trustsMfa = false,
        public bool $isEnabled = true,
    ) {}

    /**
     * @param  array<string, mixed>  $config  client_secret is stored encrypted
     */
    public static function fromSettings(array $config): self
    {
        $secret = (string) ($config['client_secret'] ?? '');

        return new self(
            key: (string) $config['key'],
            label: (string) ($config['label'] ?? $config['key']),
            issuer: rtrim((string) $config['issuer'], '/'),
            clientId: (string) $config['client_id'],
            clientSecret: $secret === '' ? '' : Crypt::decryptString($secret),
            scopes: array_values(array_filter(preg_split('/[\s,]+/', (string) ($config['scopes'] ?? 'openid email profile')) ?: [])),
            trustsMfa: (bool) ($config['trusts_mfa'] ?? false),
            isEnabled: (bool) ($config['is_enabled'] ?? true),
        );
    }

    public function redirectUri(): string
    {
        return route('sso.callback', $this->key);
    }
}
