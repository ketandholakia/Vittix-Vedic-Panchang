<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Unit\Panchanga;

use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Enum\Karana;
use Vittix\Panchang\Panchanga\KaranaCalculator;
use Vittix\Panchang\Tests\Support\MockAstronomyEngine;
use Vittix\Panchang\Time\AstroTimeFactory;

final class KaranaCalculatorBoundaryTest extends TestCase
{
    private MockAstronomyEngine $engine;
    private KaranaCalculator $calculator;

    protected function setUp(): void
    {
        $this->engine = new MockAstronomyEngine();
        $this->calculator = new KaranaCalculator($this->engine);
    }

    /**
     * @dataProvider boundaryProvider
     */
    public function testKaranaBoundaries(float $moonLon, float $sunLon, Karana $expected): void
    {
        $this->engine->moonLongitude = $moonLon;
        $this->engine->sunLongitude = $sunLon;

        $factory = new AstroTimeFactory();
        $time = $factory->fromDateTime(new \DateTimeImmutable());

        $karana = $this->calculator->calculate($time);

        self::assertSame($expected, $karana);
    }

    /**
     * @return array<int, array{0: float, 1: float, 2: Karana}>
     */
    public static function boundaryProvider(): array
    {
        return [
            // Fixed 1: 0 to 6 (First half of Shukla Pratipada)
            [0.0, 0.0, Karana::Kimstughna],
            [5.999999, 0.0, Karana::Kimstughna],
            
            // Moving 1: 6 to 12 (Second half of Shukla Pratipada)
            [6.0, 0.0, Karana::Bava],
            [11.999999, 0.0, Karana::Bava],
            
            // Moving 2: 12 to 18 (First half of Shukla Dwitiya)
            [12.0, 0.0, Karana::Balava],

            // Fixed 2: 342 to 348 (Second half of Krishna Chaturdashi)
            [342.0, 0.0, Karana::Shakuni],
            
            // Fixed 3: 348 to 354 (First half of Amavasya)
            [348.0, 0.0, Karana::Chatushpada],
            
            // Fixed 4: 354 to 360 (Second half of Amavasya)
            [354.0, 0.0, Karana::Naga],
            [359.999999, 0.0, Karana::Naga],
            
            [360.0, 0.0, Karana::Kimstughna],
        ];
    }
}
