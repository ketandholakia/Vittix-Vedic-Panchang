<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Support;

use DateTimeImmutable;
use DateTimeZone;
use Vittix\Panchang\Astronomy\Contracts\AstronomyEngineInterface;
use Vittix\Panchang\Enum\Body;
use Vittix\Panchang\ValueObject\AstroInstant;
use Vittix\Panchang\ValueObject\EclipticPosition;
use Vittix\Panchang\ValueObject\GeoLocation;
use Vittix\Panchang\ValueObject\SolarEvents;

final class MockAstronomyEngine implements AstronomyEngineInterface
{
    public float $sunLongitude = 0.0;
    public float $moonLongitude = 0.0;
    
    public function position(Body $body, AstroInstant $instant): EclipticPosition
    {
        $lon = $body === Body::Sun ? $this->sunLongitude : $this->moonLongitude;
        return new EclipticPosition($lon, 0.0, 1.0);
    }

    public function tropicalLongitude(Body $body, AstroInstant $instant): float
    {
        return $body === Body::Sun ? $this->sunLongitude : $this->moonLongitude;
    }

    public function siderealLongitude(Body $body, AstroInstant $instant): float
    {
        return $body === Body::Sun ? $this->sunLongitude : $this->moonLongitude;
    }

    public function ayanamsa(AstroInstant $instant): float
    {
        return 0.0;
    }

    public function solarEvents(
        DateTimeImmutable $localDate,
        GeoLocation $location,
        DateTimeZone $timezone
    ): SolarEvents {
        // Return dummy solar events
        return new SolarEvents(
            $localDate->setTime(6, 0),
            $localDate->setTime(12, 0),
            $localDate->setTime(18, 0)
        );
    }

    public function equatorialPosition(Body $body, AstroInstant $instant): \Vittix\Panchang\ValueObject\EquatorialPosition
    {
        return new \Vittix\Panchang\ValueObject\EquatorialPosition(0.0, 0.0);
    }

    public function dailyMotion(Body $body, AstroInstant $instant): float
    {
        return 1.0;
    }

    public function lunarEvents(DateTimeImmutable $localDate, GeoLocation $location, DateTimeZone $timezone): \Vittix\Panchang\ValueObject\LunarEvents
    {
        return new \Vittix\Panchang\ValueObject\LunarEvents(null, null, null);
    }

    public function siderealTime(AstroInstant $instant, GeoLocation $location): float
    {
        return 0.0;
    }
}

