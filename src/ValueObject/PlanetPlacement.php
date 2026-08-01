<?php

declare(strict_types=1);

namespace Vittix\Panchang\ValueObject;

use Vittix\Panchang\Enum\Body;
use Vittix\Panchang\Enum\Rashi;

/**
 * Represents a planetary body's placement in a Kundali (Birth Chart).
 */
final class PlanetPlacement
{
    public function __construct(
        public readonly Body $body,
        public readonly float $longitude,
        public readonly Rashi $rashi,
        public readonly int $house,
    ) {
    }
}
