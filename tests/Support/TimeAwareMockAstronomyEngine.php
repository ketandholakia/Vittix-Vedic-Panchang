<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Support;

use DateTimeZone;
use Vittix\Panchang\Astronomy\Contracts\AstronomyEngineInterface;
use Vittix\Panchang\Enum\Body;
use Vittix\Panchang\Math\Angle;
use Vittix\Panchang\ValueObject\AstroInstant;
use Vittix\Panchang\ValueObject\SolarEvents;
use Vittix\Panchang\ValueObject\GeoLocation;

/**
 * A mock engine that simulates the actual movement of the Sun and Moon.
 * Epoch: 2000-01-01 12:00:00 UTC (J2000.0) -> JD 2451545.0
 * Sun moves 360/365.25 deg per day
 * Moon moves 360/27.32 deg per day
 */
final class TimeAwareMockAstronomyEngine implements AstronomyEngineInterface
{
    private const J2000 = 2451545.0;
    
    // Initial longitudes at J2000
    public float $sunEpochLon = 280.0;
    public float $moonEpochLon = 220.0;
    
    public float $sunDailyMotion = 0.9856; // degrees per day
    public float $moonDailyMotion = 13.176; // degrees per day

    public function position(Body $body, AstroInstant $instant): \Vittix\Panchang\ValueObject\EclipticPosition
    {
        return new \Vittix\Panchang\ValueObject\EclipticPosition(
            $this->tropicalLongitude($body, $instant),
            0.0,
            1.0
        );
    }

    public function tropicalLongitude(Body $body, AstroInstant $instant): float
    {
        $days = $instant->julianDayUt - self::J2000;

        return match ($body) {
            Body::Sun => Angle::normalize($this->sunEpochLon + ($days * $this->sunDailyMotion)),
            Body::Moon => Angle::normalize($this->moonEpochLon + ($days * $this->moonDailyMotion)),
        };
    }

    public function siderealLongitude(Body $body, AstroInstant $instant): float
    {
        return Angle::normalize($this->tropicalLongitude($body, $instant) - $this->ayanamsa($instant));
    }

    public function ayanamsa(AstroInstant $instant): float
    {
        return 24.0; // Fixed ayanamsa for mock
    }

    public function solarEvents(\DateTimeImmutable $localDate, GeoLocation $location, DateTimeZone $timezone): SolarEvents
    {
        return new SolarEvents(null, null, null);
    }
}
