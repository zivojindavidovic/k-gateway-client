{{--
    <x-gateway::client />: browser klijent k-gateway-a (/bff/sdk.js, izvor u sdk/). Prati prijavu i odjavu
    u svim tabovima i aplikacijama, prijava u popup-u, zamena korisnika. Samo na zahtevu koji je prošao
    gateway.auth; data-sub vezuje stranu za korisnika za koga je napravljena.
--}}
@php($gatewayUser = request()->attributes->get(\Kancelarko\GatewayClient\GatewayUser::class))
@if ($gatewayUser instanceof \Kancelarko\GatewayClient\GatewayUser)
    <script
        src="{{ \Kancelarko\GatewayClient\GatewayUrls::sdk() }}"
        data-sub="{{ $gatewayUser->subject }}"
        data-poll="{{ config('gateway.client.poll') }}"
        data-on-unauthenticated="{{ config('gateway.client.on_unauthenticated') }}"
        defer
    ></script>
@endif
