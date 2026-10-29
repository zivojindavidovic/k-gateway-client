<?php

declare(strict_types=1);

namespace Kancelarko\GatewayClient\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Kancelarko\GatewayClient\GatewayUser;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * `gateway.same-user`: Laravel sesija (i CSRF token u njoj) pripada jednom korisniku gateway-a.
 *
 * Sesija gateway-a je zajednička za sve tabove i aplikacije: kad se u drugom tabu prijavi drugi korisnik
 * (ili isti na drugu licencu), strana prethodnog bi i dalje slala forme sa ispravnim CSRF tokenom, ali pod
 * novim korisnikom. Zato:
 *
 * - promenjen korisnik + zahtev koji menja stanje → 409, ništa se ne izvršava;
 * - promenjen korisnik + čitanje → nova sesija i novi CSRF token (forme stare strane posle dobijaju 419).
 *
 * Browser klijent (`/bff/sdk.js`) isto hvata pre slanja; ovo važi i bez JS-a.
 * Ide posle `gateway.auth` (oba su u grupi `gateway`).
 */
final class EnsureSameGatewayUser
{
    public const string SESSION_KEY = 'gateway.subject';

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->attributes->get(GatewayUser::class);

        if (! $user instanceof GatewayUser || ! $request->hasSession()) {
            return $next($request);
        }

        $session = $request->session();
        $previous = $session->get(self::SESSION_KEY);

        if ($previous !== null && $previous !== $user->subject) {
            if (! $request->isMethodSafe()) {
                return $this->userChanged($request);
            }

            $session->invalidate();
        }

        $session->put(self::SESSION_KEY, $user->subject);

        return $next($request);
    }

    private function userChanged(Request $request): Response
    {
        $message = 'U drugom prozoru je prijavljen drugi korisnik. Osvežite stranu.';

        if ($request->expectsJson()) {
            return new JsonResponse(['error' => 'user_changed', 'detail' => $message], Response::HTTP_CONFLICT);
        }

        throw new HttpException(Response::HTTP_CONFLICT, $message);
    }
}
