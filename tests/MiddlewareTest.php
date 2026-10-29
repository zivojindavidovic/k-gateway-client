<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Kancelarko\GatewayClient\GatewayUrls;
use Kancelarko\GatewayClient\GatewayUser;

beforeEach(function (): void {
    Route::middleware('gateway.auth')->get('/me', fn (GatewayUser $user) => [
        'license' => $user->licenseId,
        'user' => $user->userId,
        'name' => $user->name,
        'modules' => $user->modules,
    ]);
    Route::middleware(['gateway.auth', 'gateway.module:fakture'])->get('/fakture', fn () => 'ok');
    Route::middleware(['gateway.auth', 'gateway.module:kadrovik'])->get('/kadrovik', fn () => 'ok');
    Route::middleware(['gateway.auth', 'gateway.role:ADMIN'])->get('/admin', fn () => 'ok');
    Route::middleware(['gateway.auth', 'gateway.role:SUPERVISOR'])->get('/supervisor', fn () => 'ok');
    Route::middleware('gateway.auth')->get('/call', fn () => Http::viaGateway('adresar.kancelarko.local')->get('/api/subjekti')->json());
});

it('daje korisnika iz tokena i zaglavlja gateway-a', function (): void {
    $this->withToken($this->accessToken())
        ->withHeader('X-User-Name', rawurlencode('Živojin Đurić'))
        ->getJson('/me')
        ->assertOk()
        ->assertExactJson(['license' => 3, 'user' => 7, 'name' => 'Živojin Đurić', 'modules' => ['fakture', 'adresar']]);
});

it('bez tokena vraca 401 i ne preusmerava', function (): void {
    $this->get('/me')
        ->assertUnauthorized()
        ->assertHeader('WWW-Authenticate', 'Bearer error="invalid_token"');
});

it('sa neispravnim tokenom vraca 401', function (): void {
    $this->withToken($this->accessToken(['aud' => 'k-drugi']))->getJson('/me')->assertUnauthorized();
});

it('proverava modul i rolu', function (): void {
    $token = $this->accessToken();

    $this->withToken($token)->get('/fakture')->assertOk();
    $this->withToken($token)->get('/kadrovik')->assertForbidden();
    $this->withToken($token)->get('/admin')->assertOk();
    $this->withToken($token)->get('/supervisor')->assertForbidden();
});

it('poziva drugu aplikaciju kroz gateway sa istim tokenom', function (): void {
    $token = $this->accessToken();
    Http::fake(['k-gateway:9090/api/subjekti' => Http::response(['ok' => true])]);

    $this->withToken($token)->getJson('/call')->assertExactJson(['ok' => true]);

    Http::assertSent(fn ($request) => $request->url() === 'http://k-gateway:9090/api/subjekti'
        && $request->hasHeader('Host', 'adresar.kancelarko.local')
        && $request->hasHeader('Authorization', "Bearer {$token}"));
});

it('zna putanje gateway-a', function (): void {
    expect(GatewayUrls::logout())->toBe('/logout')
        ->and(GatewayUrls::account())->toBe('/account')
        ->and(GatewayUrls::sdk())->toBe('/bff/sdk.js')
        ->and(GatewayUrls::login('/invoices?a=1'))->toBe('/bff/login?returnTo=%2Finvoices%3Fa%3D1');
});
