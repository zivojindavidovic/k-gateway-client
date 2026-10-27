<?php

declare(strict_types=1);

use Kancelarko\GatewayClient\Exceptions\InvalidToken;
use Kancelarko\GatewayClient\TokenVerifier;

it('prihvata access token od k-auth-a', function (): void {
    $claims = app(TokenVerifier::class)->verify($this->accessToken());

    expect($claims->sub)->toBe('1:7')
        ->and($claims->license_id)->toBe(3);
});

it('odbija token koji nije at+jwt', function (): void {
    app(TokenVerifier::class)->verify($this->accessToken(header: ['typ' => 'JWT']));
})->throws(InvalidToken::class, 'typ');

it('odbija tudji issuer', function (): void {
    app(TokenVerifier::class)->verify($this->accessToken(['iss' => 'http://drugi-idp.local']));
})->throws(InvalidToken::class, 'iss');

it('odbija token za drugog klijenta', function (): void {
    app(TokenVerifier::class)->verify($this->accessToken(['aud' => 'k-drugi']));
})->throws(InvalidToken::class, 'aud');

it('prihvata aud kao listu', function (): void {
    expect(app(TokenVerifier::class)->verify($this->accessToken(['aud' => ['k-drugi', 'k-gateway']]))->sub)->toBe('1:7');
});

it('odbija istekao token posle tolerancije, a prihvata u okviru nje', function (): void {
    $verifier = app(TokenVerifier::class);

    expect($verifier->verify($this->accessToken(['exp' => time() - 30]))->sub)->toBe('1:7');
    expect(fn () => $verifier->verify($this->accessToken(['exp' => time() - 120])))->toThrow(InvalidToken::class);
});

it('odbija token potpisan drugim kljucem', function (): void {
    $other = self::generateKey('test-key');

    app(TokenVerifier::class)->verify($this->accessToken(privateKey: $other['private']));
})->throws(InvalidToken::class);

it('na nepoznat kid osvezava kljuceve (rotacija)', function (): void {
    $verifier = app(TokenVerifier::class);
    $verifier->verify($this->accessToken());

    $new = self::generateKey('new-key');
    $this->publishedKeys[] = $new['jwk'];

    expect($verifier->verify($this->accessToken(privateKey: $new['private'], kid: 'new-key'))->sub)->toBe('1:7');
});

it('odbija nesto sto nije JWT', function (): void {
    app(TokenVerifier::class)->verify('nije-token');
})->throws(InvalidToken::class, 'nije JWT');
