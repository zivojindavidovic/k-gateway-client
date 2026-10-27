<?php

declare(strict_types=1);

namespace Kancelarko\GatewayClient\Exceptions;

use RuntimeException;

/**
 * Token nedostaje, nije ispravan ili nije za ovu aplikaciju.
 */
final class InvalidToken extends RuntimeException {}
