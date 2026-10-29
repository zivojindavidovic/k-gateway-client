<?php

declare(strict_types=1);

namespace Kancelarko\GatewayClient;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Http\Client\Factory as Http;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Kancelarko\GatewayClient\Http\Middleware\AuthenticateGateway;
use Kancelarko\GatewayClient\Http\Middleware\EnsureSameGatewayUser;
use Kancelarko\GatewayClient\Http\Middleware\RequireModule;
use Kancelarko\GatewayClient\Http\Middleware\RequireRole;

final class GatewayServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/gateway.php', 'gateway');

        $this->app->singleton(JwksKeyProvider::class, fn ($app): JwksKeyProvider => new JwksKeyProvider(
            $app->make(Http::class),
            $app->make(Cache::class),
            (string) $app['config']->get('gateway.jwks_url'),
            (int) $app['config']->get('gateway.jwks_cache_ttl'),
        ));

        $this->app->singleton(TokenVerifier::class, fn ($app): TokenVerifier => new TokenVerifier(
            $app->make(JwksKeyProvider::class),
            (string) $app['config']->get('gateway.issuer'),
            (string) $app['config']->get('gateway.audience'),
            (int) $app['config']->get('gateway.leeway'),
        ));
    }

    public function boot(Router $router): void
    {
        $this->publishes([__DIR__.'/../config/gateway.php' => config_path('gateway.php')], 'gateway-config');

        $router->aliasMiddleware('gateway.auth', AuthenticateGateway::class);
        $router->aliasMiddleware('gateway.module', RequireModule::class);
        $router->aliasMiddleware('gateway.role', RequireRole::class);
        $router->aliasMiddleware('gateway.same-user', EnsureSameGatewayUser::class);

        // Ono što aplikacija iza gateway-a stavlja na rute: Route::middleware('gateway').
        $router->middlewareGroup('gateway', [AuthenticateGateway::class, EnsureSameGatewayUser::class]);

        // <x-gateway::client />
        Blade::anonymousComponentPath(__DIR__.'/../resources/views/components', 'gateway');

        /*
         * Poziv druge aplikacije kroz gateway, u ime istog korisnika:
         * Http::viaGateway('adresar.kancelarko.local')->get('/api/subjekti').
         */
        Http::macro('viaGateway', function (string $host): PendingRequest {
            /** @var Http $this */
            $request = $this->baseUrl((string) config('gateway.internal_url'))->withHeaders(['Host' => $host]);
            $token = app(Request::class)->bearerToken();

            return $token === null ? $request : $request->withToken($token);
        });
    }
}
