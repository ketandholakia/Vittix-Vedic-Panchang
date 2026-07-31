<?php

declare(strict_types=1);

namespace Vittix\Panchang\ValueObject;

use InvalidArgumentException;

/**
 * Represents an observer's geographic location on the Earth's surface.
 */
final readonly class GeoLocation
{
    /**
     * @param float $latitude       Geographic latitude in decimal degrees (-90 … +90).
     *                              Positive = North, Negative = South.
     * @param float $longitude      Geographic longitude in decimal degrees (-180 … +180).
     *                              Positive = East, Negative = West.
     * @param float $elevationMeters Elevation above mean sea level in metres. Defaults to 0.
     */
    public function __construct(
        public readonly float $latitude,
        public readonly float $longitude,
        public readonly float $elevationMeters = 0.0,
    ) {
        if ($latitude < -90.0 || $latitude > 90.0) {
            throw new InvalidArgumentException(
                "Latitude must be between -90 and 90, got {$latitude}."
            );
        }

        if ($longitude < -180.0 || $longitude > 180.0) {
            throw new InvalidArgumentException(
                "Longitude must be between -180 and 180, got {$longitude}."
            );
        }
    }

    /**
     * Latitude in radians.
     */
    public function latitudeRadians(): float
    {
        return deg2rad($this->latitude);
    }

    /**
     * Longitude in radians.
     */
    public function longitudeRadians(): float
    {
        return deg2rad($this->longitude);
    }

    public function __toString(): string
    {
        return sprintf('%.4f°, %.4f° (%.0fm)', $this->latitude, $this->longitude, $this->elevationMeters);
    }
}
