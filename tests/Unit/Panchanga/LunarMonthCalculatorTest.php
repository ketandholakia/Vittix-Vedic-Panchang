<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Unit\Panchanga;

use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Enum\LunarMonth;
use Vittix\Panchang\Calendar\LunarMonthCalculator;
use Vittix\Panchang\Panchanga\TransitionFinder;
use Vittix\Panchang\Tests\Support\TimeAwareMockAstronomyEngine;
use Vittix\Panchang\Time\AstroTimeFactory;
use Vittix\Panchang\Time\JulianDay;

final class LunarMonthCalculatorTest extends TestCase
{
    private TimeAwareMockAstronomyEngine $engine;
    private LunarMonthCalculator $calculator;

    protected function setUp(): void
    {
        $this->engine = new TimeAwareMockAstronomyEngine();
        $this->calculator = new LunarMonthCalculator($this->engine);
    }

    /**
     * @dataProvider boundaryProvider
     */
    public function testLunarMonthBoundaries(float $sunSiderealLonAtNewMoon, LunarMonth $expected): void
    {
        // The engine moves the Sun based on time. We want the Sun to be at $sunSiderealLonAtNewMoon 
        // at the new moon. Since new moon is when tropical elong is 0, we can set the epoch such 
        // that J2000 has 0 elongation and Sun is at the right position.
        $this->engine->sunEpochLon = $sunSiderealLonAtNewMoon + 24.0; // Sidereal + Ayanamsa
        $this->engine->moonEpochLon = $this->engine->sunEpochLon; // 0 elongation at J2000


        $factory = new AstroTimeFactory();
        
        // This time represents J2000 where our mock ensures a new moon exactly occurs.
        $newMoonTime = $factory->fromJulianDay(2451545.0);

        // LunarMonthCalculator derives the month purely from sidereal solar longitude.
        $month = $this->calculator->amantaMonth($newMoonTime);

        self::assertSame($expected, $month);
    }

    public static function boundaryProvider(): array
    {
        return [
            // Starting at 340° (Sign 11). Sun moves ~29.1° during lunation.
            // 340° + 29.1° = 369.1° (9.1°). Crosses 360° (0°) into Sign 0.
            // Sign 0 entered -> Chaitra.
            [340.0, LunarMonth::Chaitra],

            // Starting at 10° (Sign 0). Sun moves ~29.1°.
            // 10° + 29.1° = 39.1°. Crosses 30° into Sign 1.
            // Sign 1 entered -> Vaishakha.
            [10.0, LunarMonth::Vaishakha],
            
            // Starting at 40° (Sign 1). Sun moves ~29.1°.
            // 40° + 29.1° = 69.1°. Crosses 60° into Sign 2.
            // Sign 2 entered -> Jyeshtha.
            [40.0, LunarMonth::Jyeshtha],

            // Starting at 310° (Sign 10). Sun moves ~29.1°.
            // 310° + 29.1° = 339.1°. Crosses 330° into Sign 11.
            // Sign 11 entered -> Phalguna.
            [310.0, LunarMonth::Phalguna],
        ];
    }
    public function testNextNewMoonFromComputedNewMoonAdvancesOneLunation(): void
    {
        $factory = new AstroTimeFactory();
        $start = $factory->fromDateTime(
            new \DateTimeImmutable('2026-07-31T00:40:00+05:30')
        );

        $first = $this->calculator->nextNewMoon($start);
        $second = $this->calculator->nextNewMoon($first);

        $differenceDays = $second->julianDayUt - $first->julianDayUt;

        self::assertGreaterThan(25.0, $differenceDays);
        self::assertLessThan(35.0, $differenceDays);
    }

    public function testPreviousNewMoonAtBoundaryIsInclusive(): void
    {
        $factory = new AstroTimeFactory();
        $start = $factory->fromDateTime(
            new \DateTimeImmutable('2026-07-31T00:40:00+05:30')
        );
        $newMoon = $this->calculator->nextNewMoon($start);

        $previous = $this->calculator->previousNewMoon($newMoon);

        self::assertEqualsWithDelta(
            $newMoon->julianDayUt,
            $previous->julianDayUt,
            10.0 / 86400.0
        );
    }
}
