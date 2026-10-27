# kancelarko/gateway-client

Kako Laravel aplikacija živi iza **k-gateway-a**.

```
k-auth ◄──── k-gateway ────► aplikacije (k-fakture, k-otpremnice…)
 (IdP)         (BFF)
```

Aplikacija ne zna za k-auth. Browser ide kroz gateway (`fakture.kancelarko.local` → gateway → aplikacija),
gateway radi prijavu i svakom zahtevu dodaje `Authorization: Bearer <at+jwt>`. Aplikacija samo proverava
taj token; javne ključeve čita sa gateway-a (`/.well-known/jwks.json`).

## Instalacija

```bash
composer require kancelarko/gateway-client
```

`.env` (vrednosti za k-docker):

```
GATEWAY_JWKS_URL=http://k-gateway:9090/.well-known/jwks.json
GATEWAY_ISSUER=http://auth.kancelarko.local
GATEWAY_INTERNAL_URL=http://k-gateway:9090
```

Aplikacija mora da veruje gateway-u kao proxy-ju (`trustProxies`), da `url()`/`route()` prave linkove
na javni domen.

## Upotreba

| Šta | Kako |
|---|---|
| Zahteva prijavu | middleware `gateway.auth` (bez tokena → 401, nikad redirect: navigaciju gateway sam šalje na prijavu) |
| Ko je prijavljen | `GatewayUser` (injekcija u kontroler/akciju): `licenseId`, `userId`, `role`, `modules`, `name`, `email` |
| Modul / rola | `gateway.module:fakture`, `gateway.role:ADMIN` |
| Poziv druge aplikacije | `Http::viaGateway('adresar.kancelarko.local')->get('/api/...')` — isti korisnik, isti token |
| Odjava, "Moj nalog" | linkovi `GatewayUrls::logout()` (`/logout`), `GatewayUrls::account()` (`/account`) |
| XHR dobio 401 | `location = GatewayUrls::login(location.href)` (`/bff/login?returnTo=…`) |
| Provera sesije iz JS-a | `GET /bff/session` → `{authenticated, user}` |

`name` i `email` dolaze iz zaglavlja gateway-a i služe samo za prikaz; odluke o pristupu idu po tokenu.

## Testovi

```bash
composer install && composer test
```
