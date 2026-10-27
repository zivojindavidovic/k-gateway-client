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
     * Prijava sa povratkom; za XHR koji je dobio 401 (navigaciju gateway sam šalje na prijavu).
     */
    public static function login(string $returnTo = '/'): string
    {
        return '/bff/login?'.http_build_query(['returnTo' => $returnTo]);
    }

    /**
     * `{authenticated, user}` za proveru iz JS-a (npr. kad se tab vrati u fokus).
     */
    public static function session(): string
    {
        return '/bff/session';
    }
}
