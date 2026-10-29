<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Aplikacija iza k-gateway-a
|--------------------------------------------------------------------------
|
| Aplikacija ne zna za k-auth: browser ide kroz gateway, a gateway svakom
| zahtevu dodaje `Authorization: Bearer <at+jwt>`. Ovde je samo ono sto treba
| za proveru tog tokena i za pozive drugih aplikacija kroz gateway.
|
| jwks_url        javni kljucevi (gateway ih prosledjuje sa k-auth-a)
| issuer          `iss` u tokenima; samo vrednost za poredjenje, ne adresa
| audience        `aud` u tokenima: klijent kome je token izdat (gateway)
| leeway          tolerancija razlike u satu (sekunde)
| jwks_cache_ttl  koliko se kljucevi kesiraju (sekunde); nepoznat `kid` osvezava ranije
| internal_url    gateway u internoj mrezi, za pozive drugih aplikacija
|
| client          browser klijent (`<x-gateway::client />`, /bff/sdk.js):
|   poll                provera sesije u sekundama dok korisnik radi na strani (najmanje 15)
|   on_unauthenticated  "dialog" (prijava u popup-u, rad na strani ostaje) ili "redirect" (odmah na prijavu)
|
*/

return [
    'jwks_url' => env('GATEWAY_JWKS_URL', 'http://k-gateway:9090/.well-known/jwks.json'),
    'issuer' => env('GATEWAY_ISSUER', 'http://auth.kancelarko.local'),
    'audience' => env('GATEWAY_AUDIENCE', 'k-gateway'),
    'leeway' => (int) env('GATEWAY_LEEWAY', 60),
    'jwks_cache_ttl' => (int) env('GATEWAY_JWKS_CACHE_TTL', 600),
    'internal_url' => env('GATEWAY_INTERNAL_URL', 'http://k-gateway:9090'),
    'client' => [
        'poll' => (int) env('GATEWAY_CLIENT_POLL', 60),
        'on_unauthenticated' => env('GATEWAY_CLIENT_ON_UNAUTHENTICATED', 'dialog'),
    ],
];
