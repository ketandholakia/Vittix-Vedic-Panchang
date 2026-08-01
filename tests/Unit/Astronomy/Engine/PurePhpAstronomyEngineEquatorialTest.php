<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Unit\Astronomy\Engine;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Astronomy\Engine\PurePhpAstronomyEngine;
use Vittix\Panchang\Enum\Body;
use Vittix\Panchang\ValueObject\AstroInstant;

class PurePhpAstronomyEngineEquatorialTest extends TestCase
{
    private PurePhpAstronomyEngine $engine;

    protected function setUp(): void
    {
        $this->engine = new PurePhpAstronomyEngine();
    }

    public function testEquatorialPosition(): void
    {
        $utc = new DateTimeImmutable('2000-01-01 12:00:00', new DateTimeZone('UTC'));
        // roughly J2000.0 TT
        $instant = new AstroInstant($utc, 2451545.0, 2451545.0 + (63.83 / 86400), 63.83);

        $eq = $this->engine->equatorialPosition(Body::Sun, $instant);

        $this->assertIsFloat($eq->rightAscension);
        $this->assertIsFloat($eq->declination);
        
        // Sun declination near J2000.0 (Winter) should be around -23 degrees
        $this->assertEqualsWithDelta(-23.0, $eq->declination, 1.0);
    }

    public function testDailyMotion(): void
    {
        $utc = new DateTimeImmutable('2000-01-01 12:00:00', new DateTimeZone('UTC'));
        $instant = new AstroInstant($utc, 2451545.0, 2451545.0 + (63.83 / 86400), 63.83);

        $sunMotion = $this->engine->dailyMotion(Body::Sun, $instant);
        $moonMotion = $this->engine->dailyMotion(Body::Moon, $instant);

        // Sun moves ~1 degree per day
        $this->assertEqualsWithDelta(1.0, $sunMotion, 0.1);
        
        // Moon moves ~13 degrees per day
        $this->assertEqualsWithDelta(13.2, $moonMotion, 2.0);
    }
}
