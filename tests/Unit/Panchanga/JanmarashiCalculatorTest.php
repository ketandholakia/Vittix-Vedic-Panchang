<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Unit\Panchanga;

use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Astronomy\Engine\PurePhpAstronomyEngine;
use Vittix\Panchang\Enum\Rashi;
use Vittix\Panchang\Panchanga\JanmarashiCalculator;
use Vittix\Panchang\Tests\Support\MockAstronomyEngine;
use Vittix\Panchang\Time\AstroTimeFactory;

final class JanmarashiCalculatorTest extends TestCase
{
    public function testCalculateMapsSiderealMoonLongitudeToRashi(): void
    {
        $engine = new MockAstronomyEngine();
        $engine->moonLongitude = 89.9;

        $calculator = new JanmarashiCalculator($engine);
        $instant = (new AstroTimeFactory())->fromDateTime(new \DateTimeImmutable('2026-07-31 00:00:00', new \DateTimeZone('UTC')));

        self::assertSame(Rashi::Mithuna, $calculator->calculate($instant));
        self::assertSame(2, $calculator->index($instant));
    }

    public function testRealEngineReturnsValidRashi(): void
    {
        $engine = new PurePhpAstronomyEngine();
        $calculator = new JanmarashiCalculator($engine);
        $instant = (new AstroTimeFactory())->fromDateTime(new \DateTimeImmutable('2026-07-31 00:00:00', new \DateTimeZone('UTC')));

        self::assertContains($calculator->calculate($instant), Rashi::cases());
    }
}
