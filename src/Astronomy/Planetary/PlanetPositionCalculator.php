<?php

declare(strict_types=1);

namespace Vittix\Panchang\Astronomy\Planetary;

use Vittix\Panchang\Astronomy\Coordinates\NutationCalculator;
use Vittix\Panchang\Math\Angle;
use Vittix\Panchang\Time\JulianDay;
use Vittix\Panchang\ValueObject\AstroInstant;
use Vittix\Panchang\ValueObject\EclipticPosition;
use Vittix\Panchang\Time\AstroTimeFactory;

/**
 * Calculates geocentric apparent position for a planet using VSOP87.
 * 
 * Supports Mercury, Venus, Mars, Jupiter, Saturn.
 */
final class PlanetPositionCalculator
{
    private const LIGHT_TIME_DAYS_PER_AU = 0.0057755183;
    private readonly AstroTimeFactory $timeFactory;

    public function __construct(
        private readonly string $termsClass,
        private readonly EarthPositionCalculator $earth,
        private readonly NutationCalculator $nutation,
    ) {
        $this->timeFactory = new AstroTimeFactory();
    }

    /**
     * Compute the geocentric apparent ecliptic position of the planet.
     */
    public function calculate(AstroInstant $instant): EclipticPosition
    {
        // 1. Earth's heliocentric position at true instant (JD)
        [$l0, $b0, $r0] = $this->earth->calculate($instant);

        // Convert to rectangular (x0, y0, z0)
        $x0 = $r0 * Angle::cos($b0) * Angle::cos($l0);
        $y0 = $r0 * Angle::cos($b0) * Angle::sin($l0);
        $z0 = $r0 * Angle::sin($b0);

        // 2. Initial planet heliocentric position at true instant (JD)
        [$l, $b, $r] = $this->heliocentric($instant);

        $x = $r * Angle::cos($b) * Angle::cos($l);
        $y = $r * Angle::cos($b) * Angle::sin($l);
        $z = $r * Angle::sin($b);

        // Calculate initial distance to Earth
        $dx = $x - $x0;
        $dy = $y - $y0;
        $dz = $z - $z0;
        $distance = sqrt($dx * $dx + $dy * $dy + $dz * $dz);

        // 3. Light-time correction (Meeus Ch 33, p. 224)
        $tau = self::LIGHT_TIME_DAYS_PER_AU * $distance;
        $delayedInstant = $this->timeFactory->fromJulianDay($instant->julianDayTt - $tau);

        // Re-evaluate planet position at (JD - tau)
        [$l, $b, $r] = $this->heliocentric($delayedInstant);
        
        $x = $r * Angle::cos($b) * Angle::cos($l);
        $y = $r * Angle::cos($b) * Angle::sin($l);
        $z = $r * Angle::sin($b);

        // 4. Geocentric coordinates (J2000 equinox)
        $X = $x - $x0;
        $Y = $y - $y0;
        $Z = $z - $z0;

        $geoLonJ2000 = Angle::normalize(Angle::atan2($Y, $X));
        $geoLatJ2000 = Angle::atan2($Z, sqrt($X * $X + $Y * $Y));
        $distance = sqrt($X * $X + $Y * $Y + $Z * $Z); // True distance

        // 5. Precess from J2000 to mean equinox of date (Meeus Ch 21)
        $T = $instant->julianCenturiesTt();
        
        $eta = (47.0029 * $T - 0.03302 * $T ** 2 + 0.000060 * $T ** 3) / 3600.0;
        $pi  = (174.876384 * 3600.0 + 3289.4789 * $T + 0.60622 * $T ** 2 - 0.00120 * $T ** 3) / 3600.0;
        $p   = (5029.0966 * $T + 1.11113 * $T ** 2 - 0.000132 * $T ** 3) / 3600.0;

        $sinEta = Angle::sin($eta);
        $cosEta = Angle::cos($eta);
        $sinBeta0 = Angle::sin($geoLatJ2000);
        $cosBeta0 = Angle::cos($geoLatJ2000);
        $sinLonPi = Angle::sin($geoLonJ2000 - $pi);
        $cosLonPi = Angle::cos($geoLonJ2000 - $pi);

        $A = $cosEta * $cosBeta0 * $sinLonPi - $sinEta * $sinBeta0;
        $B = $cosBeta0 * $cosLonPi;
        $C = $cosEta * $sinBeta0 + $sinEta * $cosBeta0 * $sinLonPi;

        $geoLonDate = Angle::normalize($p + $pi + Angle::atan2($A, $B));
        $geoLatDate = Angle::asin($C);

        // 6. Nutation
        [$deltaPsi] = $this->nutation->calculate($instant);

        // 7. Aberration (Low accuracy correction: -20.4898" / R)
        // Meeus Ch 33 notes that full aberration is complex, but for Vedic astrology, 
        // light-time correction already accounts for planetary aberration. 
        // The remaining annual aberration is tiny, but standard approximation:
        // Actually, simple aberration is just -20.4898" / cos(beta). But wait!
        // Planetary aberration is ALREADY handled by using the geometric position 
        // of the Earth at the time of observation, and the geometric position of 
        // the planet at the time of emission (JD - tau). This yields the APPARENT 
        // position directly! No extra aberration needed except nutation.

        $apparentLon = Angle::normalize($geoLonDate + $deltaPsi);

        return new EclipticPosition(
            longitude: $apparentLon,
            latitude: $geoLatDate,
            distance: $distance
        );
    }

    /**
     * Compute planet's heliocentric coordinates (L, B, R) at the given instant.
     *
     * @return float[] [Longitude (degrees), Latitude (degrees), Radius (AU)]
     */
    private function heliocentric(AstroInstant $instant): array
    {
        $t = ($instant->julianDayTt - JulianDay::J2000) / JulianDay::DAYS_PER_MILLENNIUM;

        $c = $this->termsClass;
        $lSeries = [
            defined("$c::L0") ? constant("$c::L0") : [],
            defined("$c::L1") ? constant("$c::L1") : [],
            defined("$c::L2") ? constant("$c::L2") : [],
            defined("$c::L3") ? constant("$c::L3") : [],
            defined("$c::L4") ? constant("$c::L4") : [],
            defined("$c::L5") ? constant("$c::L5") : [],
        ];
        $bSeries = [
            defined("$c::B0") ? constant("$c::B0") : [],
            defined("$c::B1") ? constant("$c::B1") : [],
            defined("$c::B2") ? constant("$c::B2") : [],
            defined("$c::B3") ? constant("$c::B3") : [],
            defined("$c::B4") ? constant("$c::B4") : [],
            defined("$c::B5") ? constant("$c::B5") : [],
        ];
        $rSeries = [
            defined("$c::R0") ? constant("$c::R0") : [],
            defined("$c::R1") ? constant("$c::R1") : [],
            defined("$c::R2") ? constant("$c::R2") : [],
            defined("$c::R3") ? constant("$c::R3") : [],
            defined("$c::R4") ? constant("$c::R4") : [],
            defined("$c::R5") ? constant("$c::R5") : [],
        ];

        $l = $this->evaluateSeries($lSeries, $t);
        $b = $this->evaluateSeries($bSeries, $t);
        $r = $this->evaluateSeries($rSeries, $t);

        return [
            Angle::normalize(rad2deg($l)),
            rad2deg($b),
            $r
        ];
    }

    private function evaluateSeries(array $series, float $t): float
    {
        $sum = 0.0;
        $tPower = 1.0;

        foreach ($series as $terms) {
            $termSum = 0.0;
            foreach ($terms as [$amp, $phase, $freq]) {
                $termSum += $amp * cos($phase + $freq * $t);
            }
            $sum += $termSum * $tPower;
            $tPower *= $t;
        }

        return $sum;
    }
}
