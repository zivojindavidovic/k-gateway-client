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

Browser klijent (opciono, podrazumevane vrednosti):

```
GATEWAY_CLIENT_POLL=60                     # provera sesije u sekundama dok korisnik radi
GATEWAY_CLIENT_ON_UNAUTHENTICATED=dialog   # dialog (prijava u popup-u) ili redirect
```

## Nova aplikacija iza gateway-a

1. Ruta u k-gateway-u (`Host=<app>.kancelarko...`, kao `fakture`).
2. `composer require kancelarko/gateway-client`, rute iza grupe `gateway`: `Route::middleware('gateway')`.
3. `<x-gateway::client />` u `<head>` glavnog layout-a.
4. Odjava kao POST forma na `GatewayUrls::logout()`, "Moj nalog" kao link na `GatewayUrls::account()`.

Aplikacija nema svoj kod za prijavu, odjavu, proveru sesije, preusmeravanje na 401 ni praćenje tabova.

## Upotreba

| Šta | Kako |
|---|---|
| Zahteva prijavu | grupa `gateway` = `gateway.auth` + `gateway.same-user` |
| Samo provera tokena | middleware `gateway.auth` (bez tokena → 401, nikad redirect: navigaciju gateway sam šalje na prijavu) |
| Sesija vezana za korisnika | `gateway.same-user` (posle `gateway.auth`): drugi korisnik u drugom tabu → izmena dobija 409 (`{"error":"user_changed"}`), čitanje dobija novu sesiju i CSRF token |
| Ko je prijavljen | `GatewayUser` (injekcija u kontroler/akciju): `licenseId`, `userId`, `role`, `modules`, `name`, `email` |
| Modul / rola | `gateway.module:fakture`, `gateway.role:ADMIN` |
| Browser klijent | `<x-gateway::client />`: `<script src="/bff/sdk.js" data-sub=… defer>`, samo kad je zahtev prošao `gateway.auth` |
| Poziv druge aplikacije | `Http::viaGateway('adresar.kancelarko.local')->get('/api/...')` — isti korisnik, isti token |
| Odjava | **samo POST forma** na `GatewayUrls::logout()` (`/logout`). `GET /logout` na gateway-u samo prikazuje potvrdu |
| "Moj nalog" | link na `GatewayUrls::account()` (`/account`) |

`name` i `email` dolaze iz zaglavlja gateway-a i služe samo za prikaz; odluke o pristupu idu po tokenu.

Isteklu sesiju, odjavu u drugom tabu, 401 na XHR/fetch (prijava u popup-u i ponovljen zahtev) i zamenu
korisnika rešava browser klijent. `GatewayUrls::login()` i `session()` postoje, ali aplikacijama ne trebaju.

## Browser klijent (`sdk/`)

Izvor skripte koju gateway servira na `/bff/sdk.js` (TypeScript, bez zavisnosti u runtime-u). API je na
`window.Kancelarko.gateway` (`getState()`, `getUser()`, `check()`, `login()`, `logout()`, `on()`…),
podešavanja su `data-*` atributi script taga (vidi `sdk/src/index.ts`).

Izgrađena i minifikovana skripta je u repou, `dist/sdk.js`. Gateway je ne gradi, samo je servira:

```bash
cd sdk && npm ci && npm test && npm run build    # → dist/sdk.js, commit-uje se
cp dist/sdk.js ../k-gateway/src/main/resources/bff/sdk.js
```

## Testovi

```bash
composer install && composer test
cd sdk && npm ci && npm test
```
