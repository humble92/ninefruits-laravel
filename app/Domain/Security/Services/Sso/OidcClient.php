<?php

namespace App\Domain\Security\Services\Sso;

use App\Domain\Security\Settings\AuthenticationSettings;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Throwable;

/**
 * Generic OpenID Connect client (authorization code flow with PKCE). Works with any
 * standards compliant provider: Google Workspace, Microsoft Entra ID, Keycloak, ...
 * The ID token is verified locally: signature (provider JWKS), issuer, audience, expiry
 * and nonce.
 */
class OidcClient
{
    public function __construct(
        private readonly HttpFactory $http,
        private readonly AuthenticationSettings $settings,
    ) {}

    public function provider(string $key): ?OidcProvider
    {
        foreach ($this->settings->sso_providers as $config) {
            if (($config['key'] ?? null) === $key) {
                $provider = OidcProvider::fromSettings($config);

                return $provider->isEnabled ? $provider : null;
            }
        }

        return null;
    }

    /**
     * @return list<OidcProvider>
     */
    public function enabledProviders(): array
    {
        return array_values(array_filter(
            array_map(fn (array $config) => OidcProvider::fromSettings($config), $this->settings->sso_providers),
            fn (OidcProvider $provider) => $provider->isEnabled,
        ));
    }

    public function authorizationUrl(OidcProvider $provider, string $state, string $nonce, string $codeVerifier): string
    {
        $query = http_build_query([
            'response_type' => 'code',
            'client_id' => $provider->clientId,
            'redirect_uri' => $provider->redirectUri(),
            'scope' => implode(' ', $provider->scopes),
            'state' => $state,
            'nonce' => $nonce,
            'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '='),
            'code_challenge_method' => 'S256',
        ], arg_separator: '&', encoding_type: PHP_QUERY_RFC3986);

        return $this->discovery($provider)['authorization_endpoint'].'?'.$query;
    }

    /**
     * Exchanges the code and returns the verified ID token claims.
     *
     * @return array<string, mixed>
     */
    public function exchange(OidcProvider $provider, string $code, string $codeVerifier, string $nonce): array
    {
        $response = $this->http->asForm()->post($this->discovery($provider)['token_endpoint'], [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $provider->redirectUri(),
            'client_id' => $provider->clientId,
            'client_secret' => $provider->clientSecret,
            'code_verifier' => $codeVerifier,
        ])->throw();

        $idToken = $response->json('id_token');

        if (! is_string($idToken)) {
            throw new OidcException('The provider returned no ID token.');
        }

        return $this->verify($provider, $idToken, $nonce);
    }

    /**
     * @return array<string, mixed>
     */
    public function verify(OidcProvider $provider, string $idToken, string $nonce): array
    {
        JWT::$leeway = 60;

        try {
            $claims = (array) JWT::decode($idToken, JWK::parseKeySet($this->jwks($provider), 'RS256'));
        } catch (Throwable $exception) {
            throw new OidcException('The ID token is not valid: '.$exception->getMessage(), previous: $exception);
        }

        $audience = (array) ($claims['aud'] ?? []);

        if (rtrim((string) ($claims['iss'] ?? ''), '/') !== $provider->issuer) {
            throw new OidcException('The ID token was issued by another provider.');
        }

        if (! in_array($provider->clientId, $audience, true)) {
            throw new OidcException('The ID token is meant for another client.');
        }

        if (! hash_equals($nonce, (string) ($claims['nonce'] ?? ''))) {
            throw new OidcException('The ID token does not match this sign-in attempt.');
        }

        if (! is_string($claims['sub'] ?? null) || $claims['sub'] === '') {
            throw new OidcException('The ID token has no subject.');
        }

        return $claims;
    }

    /**
     * @return array{authorization_endpoint: string, token_endpoint: string, jwks_uri: string}
     */
    private function discovery(OidcProvider $provider): array
    {
        $document = Cache::remember("oidc:{$provider->key}:discovery", 3600, fn () => $this->http
            ->get($provider->issuer.'/.well-known/openid-configuration')
            ->throw()
            ->json());

        foreach (['authorization_endpoint', 'token_endpoint', 'jwks_uri'] as $key) {
            if (! is_string($document[$key] ?? null)) {
                throw new RuntimeException("The OIDC discovery document of [{$provider->key}] has no {$key}.");
            }
        }

        return $document;
    }

    /**
     * @return array<string, mixed>
     */
    private function jwks(OidcProvider $provider): array
    {
        return Cache::remember("oidc:{$provider->key}:jwks", 3600, fn () => $this->http
            ->get($this->discovery($provider)['jwks_uri'])
            ->throw()
            ->json());
    }
}
