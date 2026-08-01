<?php

declare(strict_types=1);

namespace Vittix\Panchang\Astronomy\Contracts;

use DateTimeImmutable;
use DateTimeZone;
use Vittix\Panchang\Enum\Body;
use Vittix\Panchang\ValueObject\AstroInstant;
use Vittix\Panchang\ValueObject\EclipticPosition;
use Vittix\Panchang\ValueObject\EquatorialPosition;
use Vittix\Panchang\ValueObject\GeoLocation;
use Vittix\Panchang\ValueObject\SolarEvents;

/**
 * Core astronomy engine contract.
 *
 * All Panchang calculators depend on this interface, not on any concrete
 * implementation.  This allows the pure-PHP Meeus engine to be swapped for
 * a higher-precision backend (VSOP87/ELP2000 or a wrapped native library)
 * without touching the Panchang layer.
 *
 * Time scale notes:
 *   • Position calculations use julianDayTt from AstroInstant (Terrestrial Time).
 *   • Sunrise/sunset uses the UTC civil time from AstroInstant.
 *   • All longitudes are returned in decimal degrees [0, 360).
 */
interface AstronomyEngineInterface
{
    /**
     * Geocentric ecliptic position (longitude, latitude, distance) for a body
     * at the given moment.  Longitude is apparent (includes nutation and
     * aberration).
     */
    public function position(
        Body $body,
        AstroInstant $instant
    ): EclipticPosition;

    /**
     * Apparent geocentric ecliptic longitude of a body in degrees [0, 360),
     * measured from the vernal equinox (tropical).
     */
    public function tropicalLongitude(
        Body $body,
        AstroInstant $instant
    ): float;

    /**
     * Sidereal geocentric ecliptic longitude in degrees [0, 360),
     * computed as:  tropicalLongitude − ayanamsha.
     */
    public function siderealLongitude(
        Body $body,
        AstroInstant $instant
    ): float;

    /**
     * Ayanamsha (Lahiri by default) in degrees at the given instant.
     */
    public function ayanamsa(AstroInstant $instant): float;

    /**
     * Equatorial position (Right Ascension, Declination) for a body at the given moment.
     */
    public function equatorialPosition(Body $body, AstroInstant $instant): EquatorialPosition;

    /**
     * Instantaneous or daily average speed (daily motion) of a body in degrees/day.
     * Positive = direct, Negative = retrograde.
     */
    public function dailyMotion(Body $body, AstroInstant $instant): float;

    /**
     * Sunrise, solar noon, and sunset for a given civil date, observer location,
     * and local timezone.  Returns null for events that do not occur (polar night,
     * midnight sun).
     *
     * The convention used is:
     *   • Standard solar altitude −0.8333° (centre of Sun at geometric horizon
     *     corrected for standard atmospheric refraction of 34′ and solar semi-
     *     diameter of 16′).
     *   • Upper limb of the Sun.
     */
    public function solarEvents(
        DateTimeImmutable $localDate,
        GeoLocation $location,
        DateTimeZone $timezone
    ): SolarEvents;
}
