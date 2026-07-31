<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Integration\Panchanga;

use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Astronomy\Engine\PurePhpAstronomyEngine;
use Vittix\Panchang\Enum\Tithi;
use Vittix\Panchang\Panchanga\KaranaCalculator;
use Vittix\Panchang\Panchanga\NakshatraCalculator;
use Vittix\Panchang\Panchanga\TimelineCalculator;
use Vittix\Panchang\Panchanga\TithiCalculator;
use Vittix\Panchang\Panchanga\TransitionFinder;
use Vittix\Panchang\Panchanga\YogaCalculator;
use Vittix\Panchang\Time\AstroTimeFactory;

final class TimelineCalculatorTest extends TestCase
{
    public function testTithiWindowsForDay(): void
    {
        $engine = new PurePhpAstronomyEngine();
        $finder = new TransitionFinder($engine);
        $timeline = new TimelineCalculator(
            $finder,
            new TithiCalculator($engine),
            new NakshatraCalculator($engine),
            new YogaCalculator($engine),
            new KaranaCalculator($engine)
        );

        $factory = new AstroTimeFactory();
        
        // 2026-07-31 06:10 IST to 2026-08-01 06:11 IST
        // We know from our fixtures that Tithi ends at 22:32 IST on this day
        $from = $factory->fromDateTime(new \DateTimeImmutable('2026-07-31 06:10:00', new \DateTimeZone('Asia/Kolkata')));
        $until = $factory->fromDateTime(new \DateTimeImmutable('2026-08-01 06:11:00', new \DateTimeZone('Asia/Kolkata')));

        $windows = $timeline->getTithiWindows($from, $until);

        // We expect Dwitiya to start at 'from' and end around 22:32, then Tritiya starts.
        self::assertCount(2, $windows);

        self::assertSame(Tithi::KrishnaD2, $windows[0]['tithi']);
        self::assertNotNull($windows[0]['end']);

        // Check end time is around 22:32 IST
        $endDt = \Vittix\Panchang\Time\JulianDay::toUtc($windows[0]['end']->julianDayUt)->setTimezone(new \DateTimeZone('Asia/Kolkata'));
        $expectedEndsAt = new \DateTimeImmutable('2026-07-31T22:32:27+05:30');
        self::assertLessThanOrEqual(
            120, // 2 minutes tolerance
            abs($expectedEndsAt->getTimestamp() - $endDt->getTimestamp()),
            'Timeline Tithi end time mismatch'
        );

        self::assertSame(Tithi::KrishnaD3, $windows[1]['tithi']);
        self::assertNotNull($windows[1]['end']);
    }
}
