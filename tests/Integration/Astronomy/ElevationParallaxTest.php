<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Integration\Astronomy;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Astronomy\Engine\PurePhpAstronomyEngine;
use Vittix\Panchang\ValueObject\GeoLocation;

final class ElevationParallaxTest extends TestCase
{
    public function testSunriseIsEarlierAtHigherElevation(): void
    {
        $engine = new PurePhpAstronomyEngine();
        $date = new DateTimeImmutable('2024-03-20', new DateTimeZone('Asia/Kolkata'));
        $timezone = $date->getTimezone();

        // Sea level
        $locationSea = new GeoLocation(23.0, 72.0, 0.0);
        $solarEventsSea = $engine->solarEvents($date, $locationSea, $timezone);

        // High elevation
        $locationHigh = new GeoLocation(23.0, 72.0, 5000.0);
        $solarEventsHigh = $engine->solarEvents($date, $locationHigh, $timezone);

        self::assertNotNull($solarEventsSea->sunrise);
        self::assertNotNull($solarEventsHigh->sunrise);

        // Sunrise should be earlier at high elevation due to horizon dip
        self::assertLessThan(
            $solarEventsSea->sunrise->getTimestamp(),
            $solarEventsHigh->sunrise->getTimestamp()
        );

        // Sunset should be later at high elevation
        self::assertNotNull($solarEventsSea->sunset);
        self::assertNotNull($solarEventsHigh->sunset);
        self::assertGreaterThan(
            $solarEventsSea->sunset->getTimestamp(),
            $solarEventsHigh->sunset->getTimestamp()
        );
    }
}
