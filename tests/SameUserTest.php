<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Kancelarko\GatewayClient\Http\Middleware\EnsureSameGatewayUser;

beforeEach(function (): void {
    Route::middleware(['web', 'gateway'])->group(function (): void {
        Route::get('/page', fn () => 'ok');
        Route::post('/form', fn () => 'saved');
    });
});

it('grupa gateway trazi token', function (): void {
    $this->get('/page')->assertUnauthorized();
});

it('zahtev koji menja stanje pod drugim korisnikom iste sesije dobija 409', function (): void {
    $this->withToken($this->accessToken())->get('/page')->assertOk();

    // U drugom tabu se prijavio drugi korisnik; stara strana šalje formu.
    $this->withToken($this->accessToken(['sub' => '1:9', 'user_id' => 9]))
        ->postJson('/form')
        ->assertStatus(409)
        ->assertJson(['error' => 'user_changed']);

    $this->withToken($this->accessToken(['sub' => '1:9', 'user_id' => 9]))
        ->post('/form')
        ->assertStatus(409)
        ->assertDontSee('saved');
});

it('citanje pod drugim korisnikom pravi novu sesiju i novi CSRF token', function (): void {
    $this->withToken($this->accessToken())->get('/page')->assertOk();
    $before = session()->token();

    $this->withToken($this->accessToken(['sub' => '1:9', 'user_id' => 9]))->get('/page')->assertOk();

    expect(session()->token())->not->toBe($before)
        ->and(session(EnsureSameGatewayUser::SESSION_KEY))->toBe('1:9');
});

it('isti korisnik zadrzava sesiju', function (): void {
    $this->withToken($this->accessToken())->get('/page')->assertOk();
    $before = session()->token();

    $this->withToken($this->accessToken())->post('/form')->assertOk()->assertSee('saved');

    expect(session()->token())->toBe($before);
});
