<?php

declare(strict_types=1);

namespace Kancelarko\GatewayClient\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Kancelarko\GatewayClient\GatewayUser;
use Symfony\Component\HttpFoundation\Response;

/**
 * `gateway.module:fakture`: korisnik mora imati aktivan modul (claim `modules`).
 * Ide posle `gateway.auth`.
 */
final class RequireModule
{
    public function handle(Request $request, Closure $next, string ...$modules): Response
    {
        $user = $request->attributes->get(GatewayUser::class);

        foreach ($modules as $module) {
            if (! $user instanceof GatewayUser || ! $user->hasModule($module)) {
                return new JsonResponse(['message' => 'Modul nije dostupan.'], Response::HTTP_FORBIDDEN);
            }
        }

        return $next($request);
    }
}
