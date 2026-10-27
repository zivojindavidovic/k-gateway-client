<?php

declare(strict_types=1);

namespace Kancelarko\GatewayClient;

use Firebase\JWT\JWK;
use Firebase\JWT\Key;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Http\Client\Factory as Http;
use Kancelarko\GatewayClient\Exceptions\InvalidToken;
use Throwable;

/**
 * Javni ključevi za proveru tokena, sa gateway-a (`/.well-known/jwks.json`), keširani.
 * Nepoznat `kid` (rotacija ključeva) osvežava keš, najviše jednom u minutu.
 */
final readonly class JwksKeyProvider
{
    private const string CACHE_KEY = 'kancelarko-gateway:jwks';

    private const string REFRESHED_KEY = 'kancelarko-gateway:jwks-refreshed';

    private const int MIN_REFRESH_INTERVAL = 60;

    public function __construct(
        private Http $http,
        private Cache $cache,
        private string $jwksUrl,
        private int $cacheTtl,
    ) {}

    /**
     * @throws InvalidToken
     */
    public function key(string $kid): Key
    {
        $keys = $this->parse($this->cache->remember(self::CACHE_KEY, $this->cacheTtl, $this->fetch(...)));

        if (! isset($keys[$kid]) && $this->cache->add(self::REFRESHED_KEY, true, self::MIN_REFRESH_INTERVAL)) {
            $json = $this->fetch();
            $this->cache->put(self::CACHE_KEY, $json, $this->cacheTtl);
            $keys = $this->parse($json);
        }

        return $keys[$kid] ?? throw new InvalidToken("Nepoznat ključ (kid: {$kid}).");
    }

    private function fetch(): string
    {
        try {
            return $this->http->timeout(5)->get($this->jwksUrl)->throw()->body();
        } catch (Throwable $e) {
            throw new InvalidToken('Javni ključevi nisu dostupni: '.$e->getMessage(), previous: $e);
        }
    }

    /**
     * @return array<string, Key>
     */
    private function parse(string $json): array
    {
        try {
            return JWK::parseKeySet(json_decode($json, true, flags: JSON_THROW_ON_ERROR), 'RS256');
        } catch (Throwable $e) {
            throw new InvalidToken('Neispravan JWKS: '.$e->getMessage(), previous: $e);
        }
    }
}
