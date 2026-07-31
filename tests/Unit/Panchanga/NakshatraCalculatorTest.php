<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Unit\Panchanga;

use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Enum\Nakshatra;
use Vittix\Panchang\Panchanga\NakshatraCalculator;
use Vittix\Panchang\Tests\Support\MockAstronomyEngine;
use Vittix\Panchang\Time\AstroTimeFactory;

final class NakshatraCalculatorTest extends TestCase
{
    private MockAstronomyEngine $engine;
    private NakshatraCalculator $calculator;

    protected function setUp(): void
    {
        $this->engine = new MockAstronomyEngine();
        $this->calculator = new NakshatraCalculator($this->engine);
    }

    /**
     * @dataProvider boundaryProvider
     */
    public function testNakshatraBoundaries(float $moonLon, Nakshatra $expected): void
    {
        $this->engine->moonLongitude = $moonLon;

        $factory = new AstroTimeFactory();
        $time = $factory->fromDateTime(new \DateTimeImmutable());

        $nakshatra = $this->calculator->calculate($time);

        self::assertSame($expected, $nakshatra);
    }

    public static function boundaryProvider(): array
    {
        return [
            [0.0, Nakshatra::Ashwini],
            [13.333333, Nakshatra::Ashwini],
            [13.333334, Nakshatra::Bharani],
            [26.666666, Nakshatra::Bharani],
            [26.666667, Nakshatra::Krittika],
            [359.999999, Nakshatra::Revati],
        ];
    }
}
