<?php

declare(strict_types=1);

namespace Kancelarko\GatewayClient;

use Firebase\JWT\JWT;
use Kancelarko\GatewayClient\Exceptions\InvalidToken;
use stdClass;
use Throwable;

/**
 * Provera access tokena koji gateway prosleđuje (RFC 9068): RS256, `typ: at+jwt`,
 * potpis po JWKS-u, `iss`, `aud`, `exp`/`nbf` uz toleranciju sata.
 */
final readonly class TokenVerifier
{
    public const string TYPE = 'at+jwt';

    public function __construct(
        private JwksKeyProvider $keys,
        private string $issuer,
        private string $audience,
        private int $leeway,
    ) {}

    /**
     * @throws InvalidToken
     */
    public function verify(string $token): stdClass
    {
        $header = $this->header($token);

        if (($header['typ'] ?? null) !== self::TYPE) {
            throw new InvalidToken('Token nije access token (typ).');
        }
        if (($header['alg'] ?? null) !== 'RS256' || ! is_string($header['kid'] ?? null)) {
            throw new InvalidToken('Nepodržan algoritam ili nema kid.');
        }

        $key = $this->keys->key($header['kid']);

        try {
            JWT::$leeway = $this->leeway;
            $claims = JWT::decode($token, $key);
        } catch (Throwable $e) {
            throw new InvalidToken('Neispravan token: '.$e->getMessage(), previous: $e);
        }

        if (($claims->iss ?? null) !== $this->issuer) {
            throw new InvalidToken('Token nije izdao očekivani izdavalac (iss).');
        }
        if (! in_array($this->audience, (array) ($claims->aud ?? []), true)) {
            throw new InvalidToken('Token nije za ovu aplikaciju (aud).');
        }

        return $claims;
    }

    /**
     * @return array<string, mixed>
     */
    private function header(string $token): array
    {
        $parts = explode('.', $token);

        try {
            $header = count($parts) === 3 ? json_decode(JWT::urlsafeB64Decode($parts[0]), true, flags: JSON_THROW_ON_ERROR) : null;
        } catch (Throwable) {
            $header = null;
        }

        return is_array($header) ? $header : throw new InvalidToken('Token nije JWT.');
    }
}
