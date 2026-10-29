<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;

beforeEach(function (): void {
    $page = fn () => Blade::render('<x-gateway::client />');

    Route::middleware('gateway.auth')->get('/page', $page);
    Route::get('/public', $page);
});

it('ukljucuje browser klijent za korisnika iz tokena', function (): void {
    config(['gateway.client.poll' => 30, 'gateway.client.on_unauthenticated' => 'redirect']);

    $this->withToken($this->accessToken())
        ->get('/page')
        ->assertOk()
        ->assertSee('src="/bff/sdk.js"', escape: false)
        ->assertSee('data-sub="1:7"', escape: false)
        ->assertSee('data-poll="30"', escape: false)
        ->assertSee('data-on-unauthenticated="redirect"', escape: false);
});

it('bez tokena gateway-a ne ukljucuje browser klijent', function (): void {
    $this->get('/public')->assertOk()->assertDontSee('/bff/sdk.js', escape: false);
});
