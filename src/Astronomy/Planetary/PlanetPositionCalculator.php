<?php

declare(strict_types=1);

namespace Vittix\Panchang\Astronomy\Planetary;

use DateTimeImmutable;
use DateTimeZone;
use Vittix\Panchang\Astronomy\Coordinates\NutationCalculator;
use Vittix\Panchang\Math\Angle;
use Vittix\Panchang\Time\JulianDay;
use Vittix\Panchang\ValueObject\AstroInstant;
use Vittix\Panchang\ValueObject\EclipticPosition;
use Vittix\Panchang\Time\AstroTimeFactory;

/**
 * Calculates the geocentric apparent position of a planet using VSOP87.
 *
 * The term series used here are the VSOP87 "D" variant: geometric
 * heliocentric coordinates referred to the MEAN ECLIPTIC AND EQUINOX OF
 * DATE (the same variant tabulated by Meeus). The pipeline therefore is:
 *
 *   heliocentric (of date)
 *     → geocentric rectangular difference with light-time correction
 *       (planet at t−τ, Earth at t: this is planetary aberration)
 *     → nutation in longitude
 *     → annual aberration of the observer
 *     → apparent geocentric longitude of date
 *
 * No J2000→date precession step is applied — the series are already of
 * date.
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
        //
        // The emission instant is (observation TT − τ). Build it by shifting
        // the UTC instant by τ so the normal ΔT conversion path is used
        // exactly once. (Passing julianDayTt − τ back through
        // AstroTimeFactory::fromJulianDay() would treat it as UT and add ΔT
        // a second time, placing the emission instant ≈ ΔT too late — up to
        // ~200 s by 2100, which showed up as tens of arcseconds on fast
        // inner planets.)
        $tau = self::LIGHT_TIME_DAYS_PER_AU * $distance;
        $delayedUnix = (float) $instant->utc->format('U.u') - $tau * 86400.0;
        $delayedUtc = (new DateTimeImmutable('@' . sprintf('%.6F', $delayedUnix)))
            ->setTimezone(new DateTimeZone('UTC'));
        $delayedInstant = $this->timeFactory->fromDateTime($delayedUtc);

        // Re-evaluate planet position at (JD - tau)
        [$l, $b, $r] = $this->heliocentric($delayedInstant);
        
        $x = $r * Angle::cos($b) * Angle::cos($l);
        $y = $r * Angle::cos($b) * Angle::sin($l);
        $z = $r * Angle::sin($b);

        // 4. Geocentric coordinates referred to the mean ecliptic and
        //    equinox of date. The VSOP87 term series used above are the "D"
        //    variant (equinox of date), so no J2000→date precession step is
        //    required here.
        $X = $x - $x0;
        $Y = $y - $y0;
        $Z = $z - $z0;

        $geoLonDate = Angle::normalize(Angle::atan2($Y, $X));
        $geoLatDate = Angle::atan2($Z, sqrt($X * $X + $Y * $Y));
        $distance = sqrt($X * $X + $Y * $Y + $Z * $Z); // True distance

        // 5. Nutation in longitude (Δψ) — apparent from mean equinox of date.
        [$deltaPsi] = $this->nutation->calculate($instant);

        // 6. Annual aberration of the observer (Meeus Ch. 23, low-precision
        //    form). The planetary aberration from the light-time correction
        //    above is already included; this adds the observer's own motion:
        //        Δλ = −20.4898″ · cos(λ − ⊙) / cos β
        //    where ⊙ is the Sun's geometric longitude of date (Earth's
        //    heliocentric longitude + 180°).
        $sunGeometricLon = Angle::normalize($l0 + 180.0);
        $aberration = -20.4898
            * Angle::cos($geoLonDate - $sunGeometricLon)
            / Angle::cos($geoLatDate)
            / 3600.0;

        $apparentLon = Angle::normalize($geoLonDate + $deltaPsi + $aberration);

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

    /**
     * @param array<int, array<int, array{float, float, float}>> $series
     */
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

