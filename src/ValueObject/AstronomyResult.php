<?php

declare(strict_types=1);

namespace Vittix\Panchang\ValueObject;

use DateTimeImmutable;
use Vittix\Panchang\Enum\SolarMonth;

/**
 * Composite astronomy result for a date and location.
 *
 * This is a pure astronomical output — no Panchang, Tithi, or calendar
 * logic is included. It serves as the foundational data that higher-level
 * services (Panchang, Muhurta, Festivals) consume.
 *
 * Designed for direct serialization to JSON for the REST API.
 */
final readonly class AstronomyResult
{
    public function __construct(
        /** The civil date this result is for. */
        public readonly DateTimeImmutable $date,

        /** Observer's location. */
        public readonly GeoLocation $location,

        /** Sunrise, solar noon, and sunset. */
        public readonly SolarEvents $solarEvents,

        /** Moonrise, lunar transit, and moonset. */
        public readonly LunarEvents $lunarEvents,

        /** Lahiri Ayanamsha value in degrees. */
        public readonly float $ayanamsha,

        /** Local Mean Sidereal Time in degrees [0, 360). */
        public readonly float $siderealTime,

        /** Sun's sidereal longitude in degrees [0, 360). */
        public readonly float $sunSiderealLongitude,

        /** Moon's sidereal longitude in degrees [0, 360). */
        public readonly float $moonSiderealLongitude,

        /** Solar month derived from Sun's sidereal position. */
        public readonly SolarMonth $solarMonth,
    ) {
    }

    /**
     * Convert to a structured array suitable for JSON encoding.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'date' => $this->date->format('Y-m-d'),
            'location' => [
                'latitude' => round($this->location->latitude, 4),
                'longitude' => round($this->location->longitude, 4),
                'elevation' => $this->location->elevationMeters,
            ],
            'solar' => [
                'sunrise' => $this->solarEvents->sunrise?->format('H:i:s'),
                'solarNoon' => $this->solarEvents->solarNoon?->format('H:i:s'),
                'sunset' => $this->solarEvents->sunset?->format('H:i:s'),
                'dayLengthSeconds' => $this->solarEvents->dayLengthSeconds(),
                'siderealLongitude' => round($this->sunSiderealLongitude, 4),
            ],
            'lunar' => [
                'moonrise' => $this->lunarEvents->moonrise?->format('H:i:s'),
                'transit' => $this->lunarEvents->transit?->format('H:i:s'),
                'moonset' => $this->lunarEvents->moonset?->format('H:i:s'),
                'siderealLongitude' => round($this->moonSiderealLongitude, 4),
            ],
            'ayanamsha' => round($this->ayanamsha, 4),
            'siderealTime' => round($this->siderealTime, 4),
            'solarMonth' => [
                'name' => $this->solarMonth->nameEnglish(),
                'western' => $this->solarMonth->westernName(),
                'index' => $this->solarMonth->value,
            ],
        ];
    }

    /**
     * Serialize to JSON.
     */
    public function toJson(int $options = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE): string
    {
        return json_encode($this->toArray(), $options) ?: '{}';
    }
}
