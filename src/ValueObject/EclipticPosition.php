<?php

declare(strict_types=1);

namespace Vittix\Panchang\ValueObject;

/**
 * Geocentric ecliptic coordinates of a celestial body.
 *
 * Longitude and latitude are in decimal degrees.
 * Distance is in Astronomical Units (AU) for solar-system bodies.
 */
final readonly class EclipticPosition
{
    /**
     * @param float $longitude Ecliptic longitude in degrees [0, 360).
     * @param float $latitude  Ecliptic latitude  in degrees (-90, +90].
     * @param float $distance  Distance in AU.
     */
    public function __construct(
        public readonly float $longitude,
        public readonly float $latitude,
        public readonly float $distance,
    ) {
    }
}
