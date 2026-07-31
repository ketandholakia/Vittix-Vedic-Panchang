<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Unit\Panchanga;

use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Enum\Tithi;
use Vittix\Panchang\Panchanga\TithiCalculator;
use Vittix\Panchang\Tests\Support\MockAstronomyEngine;
use Vittix\Panchang\Time\AstroTimeFactory;

final class TithiCalculatorTest extends TestCase
{
    private MockAstronomyEngine $engine;
    private TithiCalculator $calculator;

    protected function setUp(): void
    {
        $this->engine = new MockAstronomyEngine();
        $this->calculator = new TithiCalculator($this->engine);
    }

    /**
     * @dataProvider boundaryProvider
     */
    public function testTithiBoundaries(float $moonLon, float $sunLon, Tithi $expected): void
    {
        $this->engine->moonLongitude = $moonLon;
        $this->engine->sunLongitude = $sunLon;

        $factory = new AstroTimeFactory();
        $time = $factory->fromDateTime(new \DateTimeImmutable());

        $tithi = $this->calculator->calculate($time);

        self::assertSame($expected, $tithi);
    }

    /**
     * @return array<int, array{0: float, 1: float, 2: Tithi}>
     */
    public static function boundaryProvider(): array
    {
        return [
            // 0° elongation -> Shukla Pratipada
            [0.0, 0.0, Tithi::ShuklaPratipada],
            [360.0, 0.0, Tithi::ShuklaPratipada],

            // 11.999999° -> Shukla Pratipada
            [11.999999, 0.0, Tithi::ShuklaPratipada],

            // 12° -> Shukla Dwitiya
            [12.0, 0.0, Tithi::ShuklaD2],

            // 179.999999° -> Purnima
            [179.999999, 0.0, Tithi::Purnima],

            // 180° -> Krishna Pratipada
            [180.0, 0.0, Tithi::KrishnaPratipada],

            // 191.999999° -> Krishna Pratipada
            [191.999999, 0.0, Tithi::KrishnaPratipada],

            // 192° -> Krishna Dwitiya
            [192.0, 0.0, Tithi::KrishnaD2],

            // 359.999999° -> Amavasya
            [359.999999, 0.0, Tithi::Amavasya],

            // 360° normalized 0° -> Shukla Pratipada
            [360.0, 0.0, Tithi::ShuklaPratipada],
            [10.0, 10.0, Tithi::ShuklaPratipada], // Moon and Sun at same longitude
            [30.0, 15.0, Tithi::ShuklaD2], // 15° elongation -> Shukla Dwitiya
            [15.0, 30.0, Tithi::KrishnaChaturdashi], // 345° elongation -> Krishna Chaturdashi (Index 28)
            [20.0, 30.0, Tithi::Amavasya], // 350° elongation -> Amavasya (Index 29)
        ];
    }
}
