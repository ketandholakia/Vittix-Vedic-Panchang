<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Unit\Panchanga;

use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Enum\Yoga;
use Vittix\Panchang\Panchanga\YogaCalculator;
use Vittix\Panchang\Tests\Support\MockAstronomyEngine;
use Vittix\Panchang\Time\AstroTimeFactory;

final class YogaCalculatorTest extends TestCase
{
    private MockAstronomyEngine $engine;
    private YogaCalculator $calculator;

    protected function setUp(): void
    {
        $this->engine = new MockAstronomyEngine();
        $this->calculator = new YogaCalculator($this->engine);
    }

    /**
     * @dataProvider boundaryProvider
     */
    public function testYogaBoundaries(float $moonLon, float $sunLon, Yoga $expected): void
    {
        $this->engine->moonLongitude = $moonLon;
        $this->engine->sunLongitude = $sunLon;

        $factory = new AstroTimeFactory();
        $time = $factory->fromDateTime(new \DateTimeImmutable());

        $yoga = $this->calculator->calculate($time);

        self::assertSame($expected, $yoga);
    }

    /**
     * @return array<int, array{0: float, 1: float, 2: Yoga}>
     */
    public static function boundaryProvider(): array
    {
        return [
            [0.0, 0.0, Yoga::Vishkumbha],
            [13.333333, 0.0, Yoga::Vishkumbha],
            [13.333334, 0.0, Yoga::Preeti],
            [0.0, 13.333334, Yoga::Preeti],
            [26.666666, 0.0, Yoga::Preeti],
            [26.666667, 0.0, Yoga::Ayushman],
            [359.999999, 0.0, Yoga::Vaidhriti],
            [180.0, 180.0, Yoga::Vishkumbha], // Sum 360 -> 0
        ];
    }
}
