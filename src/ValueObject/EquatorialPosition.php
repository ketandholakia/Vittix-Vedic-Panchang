<?php

declare(strict_types=1);

namespace Vittix\Panchang\ValueObject;

/**
 * Geocentric equatorial coordinates of a celestial body.
 *
 * Right ascension and declination are in decimal degrees.
 */
final readonly class EquatorialPosition
{
    /**
     * @param float $rightAscension Right ascension in degrees [0, 360).
     * @param float $declination    Declination in degrees (-90, +90].
     */
    public function __construct(
        public readonly float $rightAscension,
        public readonly float $declination,
    ) {
    }

    /**
     * Right ascension in hours (0–24).
     */
    public function rightAscensionHours(): float
    {
        return $this->rightAscension / 15.0;
    }
}
