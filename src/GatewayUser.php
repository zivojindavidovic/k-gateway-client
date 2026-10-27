<?php

declare(strict_types=1);

namespace Kancelarko\GatewayClient;

use stdClass;

/**
 * Prijavljeni korisnik, iz access tokena koji je prosledio gateway.
 *
 * `name` i `email` dolaze iz zaglavlja gateway-a (X-User-Name, X-User-Email) i služe samo za prikaz;
 * sve odluke o pristupu idu po claim-ovima tokena.
 */
final readonly class GatewayUser
{
    /**
     * @param  list<string>  $modules
     */
    public function __construct(
        public string $subject,
        public ?string $sessionId,
        public ?int $licenseId,
        public ?int $userId,
        public ?string $role,
        public array $modules,
        public bool $termsAccepted,
        public ?string $name,
        public ?string $email,
    ) {}

    public static function fromClaims(stdClass $claims, ?string $name = null, ?string $email = null): self
    {
        return new self(
            subject: (string) $claims->sub,
            sessionId: isset($claims->sid) ? (string) $claims->sid : null,
            licenseId: isset($claims->license_id) ? (int) $claims->license_id : null,
            userId: isset($claims->user_id) ? (int) $claims->user_id : null,
            role: isset($claims->role) ? (string) $claims->role : null,
            modules: array_values(array_map(strval(...), (array) ($claims->modules ?? []))),
            termsAccepted: (bool) ($claims->terms_accepted ?? false),
            name: $name,
            email: $email,
        );
    }

    public function hasModule(string $module): bool
    {
        return in_array($module, $this->modules, true);
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }
}
