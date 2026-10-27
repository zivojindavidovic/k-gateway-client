<?php

declare(strict_types=1);

namespace Kancelarko\GatewayClient\Http\Middleware;

use Closure;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Kancelarko\GatewayClient\Exceptions\InvalidToken;
use Kancelarko\GatewayClient\GatewayUser;
use Kancelarko\GatewayClient\TokenVerifier;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * `gateway.auth`: zahtev mora nositi ispravan access token od gateway-a.
 *
 * Prijavu radi gateway (neprijavljenog korisnika on šalje na k-auth), pa ovde nema preusmeravanja:
 * zahtev bez tokena znači da nije prošao kroz gateway → 401.
 */
final readonly class AuthenticateGateway
{
    public function __construct(
        private TokenVerifier $verifier,
        private Container $container,
        private LoggerInterface $log,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        try {
            $claims = $token === null ? throw new InvalidToken('Nema tokena.') : $this->verifier->verify($token);
        } catch (InvalidToken $e) {
            $this->log->info('Odbijen zahtev bez ispravnog tokena gateway-a', ['reason' => $e->getMessage(), 'path' => $request->path()]);

            return new JsonResponse(
                ['message' => 'Neophodna je prijava.'],
                Response::HTTP_UNAUTHORIZED,
                ['WWW-Authenticate' => 'Bearer error="invalid_token"'],
            );
        }

        $user = GatewayUser::fromClaims(
            $claims,
            $this->displayHeader($request, 'X-User-Name'),
            $this->displayHeader($request, 'X-User-Email'),
        );

        $this->container->instance(GatewayUser::class, $user);
        $request->attributes->set(GatewayUser::class, $user);

        return $next($request);
    }

    // Gateway šalje percent-encoded UTF-8 (zaglavlja su ASCII, imena imaju naša slova).
    private function displayHeader(Request $request, string $name): ?string
    {
        $value = $request->headers->get($name);

        return $value === null || $value === '' ? null : rawurldecode($value);
    }
}
