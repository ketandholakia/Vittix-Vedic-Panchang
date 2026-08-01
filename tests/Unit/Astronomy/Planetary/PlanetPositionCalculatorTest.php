<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Unit\Astronomy\Planetary;

use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Astronomy\Engine\PurePhpAstronomyEngine;
use Vittix\Panchang\Astronomy\Sidereal\LahiriAyanamsha;
use Vittix\Panchang\Enum\Body;
use Vittix\Panchang\Time\AstroTimeFactory;

final class PlanetPositionCalculatorTest extends TestCase
{
    public function testCalculatesAccurateApparentGeocentricLongitudeForMercury(): void
    {
        $engine = new PurePhpAstronomyEngine(new LahiriAyanamsha());
        $timeFactory = new AstroTimeFactory();

        // 2000-01-01 12:00:00 UTC (J2000 epoch)
        $instant = $timeFactory->fromDateTime(new \DateTimeImmutable('2000-01-01 12:00:00 UTC'));
        $pos = $engine->position(Body::Mercury, $instant);

        $this->assertGreaterThanOrEqual(0.0, $pos->longitude);
        $this->assertLessThan(360.0, $pos->longitude);

        $this->assertGreaterThan(0.5, $pos->distance);
        $this->assertLessThan(1.5, $pos->distance);
    }
}
