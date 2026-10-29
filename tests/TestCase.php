<?php

declare(strict_types=1);

namespace Kancelarko\GatewayClient\Tests;

use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Http;
use Kancelarko\GatewayClient\GatewayServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected const string JWKS_URL = 'http://k-gateway.test/.well-known/jwks.json';

    protected const string ISSUER = 'http://auth.kancelarko.local';

    /** @var array{private: string, jwk: array<string, string>} */
    private static array $key;

    /** Ključevi koje "gateway" trenutno objavljuje (test ih menja za rotaciju). */
    protected array $publishedKeys = [];

    protected function setUp(): void
    {
        parent::setUp();

        self::$key ??= self::generateKey('test-key');
        $this->publishedKeys = [self::$key['jwk']];
        Http::fake([self::JWKS_URL => fn () => Http::response(['keys' => $this->publishedKeys])]);
    }

    protected function getPackageProviders($app): array
    {
        return [GatewayServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('gateway.jwks_url', self::JWKS_URL);
        $app['config']->set('gateway.issuer', self::ISSUER);
        $app['config']->set('cache.default', 'array');
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('k', 32)));
    }

    /**
     * Access token kakav izdaje k-auth (claim-ovi se mogu pregaziti).
     *
     * @param  array<string, mixed>  $claims
     * @param  array<string, mixed>  $header
     */
    protected function accessToken(array $claims = [], array $header = [], ?string $privateKey = null, string $kid = 'test-key'): string
    {
        $now = time();

        return JWT::encode(array_merge([
            'iss' => self::ISSUER,
            'sub' => '1:7',
            'aud' => 'k-gateway',
            'client_id' => 'k-gateway',
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + 600,
            'jti' => 'jti-1',
            'sid' => 'sid-1',
            'license_id' => 3,
            'user_id' => 7,
            'role' => 'ADMIN',
            'modules' => ['fakture', 'adresar'],
            'terms_accepted' => true,
        ], $claims), $privateKey ?? self::$key['private'], 'RS256', $kid, array_merge(['typ' => 'at+jwt'], $header));
    }

    /**
     * @return array{private: string, jwk: array<string, string>}
     */
    protected static function generateKey(string $kid): array
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($key, $private);
        $rsa = openssl_pkey_get_details($key)['rsa'];

        return [
            'private' => $private,
            'jwk' => [
                'kty' => 'RSA',
                'use' => 'sig',
                'alg' => 'RS256',
                'kid' => $kid,
                'n' => JWT::urlsafeB64Encode($rsa['n']),
                'e' => JWT::urlsafeB64Encode($rsa['e']),
            ],
        ];
    }
}
