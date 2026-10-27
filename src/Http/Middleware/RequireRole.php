<?php

declare(strict_types=1);

namespace Kancelarko\GatewayClient\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Kancelarko\GatewayClient\GatewayUser;
use Symfony\Component\HttpFoundation\Response;

/**
 * `gateway.role:ADMIN,SUPERVISOR`: korisnik mora imati jednu od rola (claim `role`).
 * Ide posle `gateway.auth`.
 */
final class RequireRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->attributes->get(GatewayUser::class);

        if (! $user instanceof GatewayUser || ! in_array($user->role, $roles, true)) {
            return new JsonResponse(['message' => 'Nemate pravo pristupa.'], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
