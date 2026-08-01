<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Unit\Astronomy\Engine;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Astronomy\Engine\PurePhpAstronomyEngine;
use Vittix\Panchang\Enum\Body;
use Vittix\Panchang\Time\AstroTimeFactory;

final class PurePhpAstronomyEngineNodesTest extends TestCase
{
    public function testKetuIsAlwaysExactly180DegreesFromRahu(): void
    {
        $engine = new PurePhpAstronomyEngine();
        $instant = (new AstroTimeFactory())->fromDateTime(
            new DateTimeImmutable('2000-06-15 12:00:00', new DateTimeZone('UTC'))
        );

        $rahu = $engine->tropicalLongitude(Body::RahuMean, $instant);
        $ketu = $engine->tropicalLongitude(Body::KetuMean, $instant);

        self::assertEqualsWithDelta(180.0, fmod($rahu - $ketu + 360.0, 360.0), 1e-9);
    }

    public function testRahuRegressesOverTime(): void
    {
        // The lunar node regresses (moves backward through the zodiac,
        // roughly -19°/year) — unlike every other body this engine
        // supports. Two instants ~30 days apart should show a small
        // *negative* forward motion once un-wrapped.
        $engine = new PurePhpAstronomyEngine();
        $timeFactory = new AstroTimeFactory();

        $t1 = $timeFactory->fromDateTime(new DateTimeImmutable('2000-01-01 00:00:00', new DateTimeZone('UTC')));
        $t2 = $timeFactory->fromDateTime(new DateTimeImmutable('2000-01-31 00:00:00', new DateTimeZone('UTC')));

        $lon1 = $engine->tropicalLongitude(Body::RahuMean, $t1);
        $lon2 = $engine->tropicalLongitude(Body::RahuMean, $t2);

        $delta = fmod($lon2 - $lon1 + 360.0, 360.0);

        // Regression of ~19°/year ≈ 1.6° per 30 days, so the "forward"
        // delta (mod 360) should be close to 360 - 1.6, i.e. large, not
        // a small positive number.
        self::assertGreaterThan(355.0, $delta);
    }

    public function testSiderealLongitudeIsAyanamshaOffsetFromTropical(): void
    {
        $engine = new PurePhpAstronomyEngine();
        $instant = (new AstroTimeFactory())->fromDateTime(
            new DateTimeImmutable('2000-06-15 12:00:00', new DateTimeZone('UTC'))
        );

        $tropical = $engine->tropicalLongitude(Body::RahuMean, $instant);
        $sidereal = $engine->siderealLongitude(Body::RahuMean, $instant);
        $ayanamsha = $engine->ayanamsa($instant);

        self::assertEqualsWithDelta(
            fmod($tropical - $ayanamsha + 360.0, 360.0),
            $sidereal,
            1e-9
        );
    }
}
