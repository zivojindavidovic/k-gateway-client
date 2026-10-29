<?php

declare(strict_types=1);

namespace Kancelarko\GatewayClient;

/**
 * Putanje gateway-a na domenu aplikacije (gateway ih ne prosleđuje aplikaciji).
 * Aplikacija ne zna adresu k-auth-a: odjava i "Moj nalog" idu preko gateway-a.
 */
final class GatewayUrls
{
    public static function logout(): string
    {
        return '/logout';
    }

    public static function account(): string
    {
        return '/account';
    }

    /**
     * Browser klijent gateway-a; uključuje ga `<x-gateway::client />`.
     */
    public static function sdk(): string
    {
        return '/bff/sdk.js';
    }

    /**
     * Prijava sa povratkom (`returnTo`). Aplikacijama ne treba: 401 rešava browser klijent.
     */
    public static function login(string $returnTo = '/'): string
    {
        return '/bff/login?'.http_build_query(['returnTo' => $returnTo]);
    }

    /**
     * `{authenticated, user}`. Aplikacijama ne treba: sesiju prati browser klijent.
     */
    public static function session(): string
    {
        return '/bff/session';
    }
}
