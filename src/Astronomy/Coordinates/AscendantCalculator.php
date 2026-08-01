<?php

declare(strict_types=1);

namespace Vittix\Panchang\Astronomy\Coordinates;

use Vittix\Panchang\Astronomy\Contracts\AstronomyEngineInterface;
use Vittix\Panchang\Astronomy\Sidereal\SiderealTimeCalculator;
use Vittix\Panchang\Enum\Rashi;
use Vittix\Panchang\Math\Angle;
use Vittix\Panchang\ValueObject\AscendantPosition;
use Vittix\Panchang\ValueObject\AstroInstant;
use Vittix\Panchang\ValueObject\GeoLocation;

/**
 * Ascendant (Lagna) calculator.
 *
 * The Ascendant is the point of the ecliptic rising on the eastern horizon
 * at a given moment and location — the reference point for the entire
 * house system (Bhava) in a Kundali / natal chart.
 *
 * Formula (Meeus, "Astronomical Algorithms" 2nd ed., Ch. 32, eq. 32.1,
 * rearranged for longitude of the Ascendant on the ecliptic):
 *
 *   tan(λ_asc) = −cos(θ) / (sin(ε)·tan(φ) + cos(ε)·sin(θ))
 *
 * where:
 *   θ = Local Sidereal Time (RAMC), expressed as an angle
 *   ε = true obliquity of the ecliptic
 *   φ = observer's geographic latitude
 *
 * Implemented here via atan2 for correct quadrant resolution rather than a
 * bare tan()/atan(), matching the atan2-based style already used elsewhere
 * in this codebase (e.g. TransitionFinder, SolarPositionCalculator).
 *
 * Depends on AstronomyEngineInterface (rather than a standalone
 * AyanamshaCalculatorInterface) purely to read ayanamsa() — this keeps the
 * Ascendant's sidereal conversion consistent with whichever ayanamsha the
 * caller's engine is configured with, rather than hardcoding a second,
 * potentially divergent instance.
 */
final class AscendantCalculator
{
    public function __construct(
        private readonly SiderealTimeCalculator $siderealTime,
        private readonly ObliquityCalculator $obliquity,
        private readonly AstronomyEngineInterface $astronomy,
    ) {
    }

    /**
     * Tropical ecliptic longitude of the Ascendant in degrees [0, 360).
     */
    public function tropicalLongitude(AstroInstant $instant, GeoLocation $location): float
    {
        $ramc = $this->siderealTime->localMeanSiderealTime(
            $instant->julianDayUt,
            $location->longitude
        );

        $eps = $this->obliquity->true($instant);
        $lat = $location->latitude;

        $y = -Angle::cos($ramc);
        $x = Angle::sin($eps) * Angle::tan($lat) + Angle::cos($eps) * Angle::sin($ramc);

        return Angle::normalize(Angle::atan2($y, $x));
    }

    /**
     * Sidereal ecliptic longitude of the Ascendant in degrees [0, 360),
     * i.e. tropicalLongitude() − ayanamsha.
     */
    public function siderealLongitude(AstroInstant $instant, GeoLocation $location): float
    {
        return Angle::normalize(
            $this->tropicalLongitude($instant, $location) - $this->astronomy->ayanamsa($instant)
        );
    }

    /**
     * Full Ascendant position: tropical + sidereal longitude, Rashi, and
     * degree elapsed within the Rashi.
     */
    public function calculate(AstroInstant $instant, GeoLocation $location): AscendantPosition
    {
        $tropical = $this->tropicalLongitude($instant, $location);
        $sidereal = Angle::normalize($tropical - $this->astronomy->ayanamsa($instant));
        $rashi    = Rashi::fromIndex((int) floor($sidereal / 30.0));

        return new AscendantPosition(
            tropicalLongitude: $tropical,
            siderealLongitude: $sidereal,
            rashi: $rashi,
            degreeInSign: $sidereal - ($rashi->value * 30.0),
        );
    }
}
