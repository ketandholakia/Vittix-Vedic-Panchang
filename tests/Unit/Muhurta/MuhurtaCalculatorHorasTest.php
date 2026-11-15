<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Unit\Muhurta;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Enum\HoraMode;
use Vittix\Panchang\Muhurta\MuhurtaCalculator;

/**
 * Horas.
 *
 * Traditional method: 12 day Horas divide sunrise → sunset and 12 night
 * Horas divide sunset → next sunrise (each Hora is 1/12 of its half of
 * the day, not a clock hour). The ruler sequence starts at the weekday
 * lord and continues across the night boundary (night Hora 1 = sequence
 * position 13). The legacy 24-equal-hour variant stays available behind
 * HoraMode::EqualHours.
 */
final class MuhurtaCalculatorHorasTest extends TestCase
{
    private MuhurtaCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new MuhurtaCalculator();
    }

    public function testTraditionalHorasDivideDayAndNightIntoTwelveEach(): void
    {
        $sunrise = new DateTimeImmutable('2026-06-15 06:00:00', new DateTimeZone('Asia/Kolkata'));
        $sunset = new DateTimeImmutable('2026-06-15 19:00:00', new DateTimeZone('Asia/Kolkata'));
        $nextSunrise = new DateTimeImmutable('2026-06-16 06:00:00', new DateTimeZone('Asia/Kolkata'));

        $horas = $this->calculator->getHoras($sunrise, $sunset, $nextSunrise, 0);

        self::assertCount(24, $horas);

        // Day: first 12 Horas cover sunrise → sunset in 12 equal parts
        // (13 hours of daylight here → 65-minute Horas).
        for ($i = 0; $i < 12; $i++) {
            self::assertSame(
                $sunrise->getTimestamp() + (int) round($i * 3900.0),
                $horas[$i]->start->getTimestamp(),
                "day Hora $i start"
            );
        }
        self::assertSame($sunset->getTimestamp(), $horas[11]->end->getTimestamp());

        // Night: last 12 Horas cover sunset → next sunrise.
        for ($i = 12; $i < 24; $i++) {
            self::assertSame(
                $sunset->getTimestamp() + (int) round(($i - 12) * 3300.0),
                $horas[$i]->start->getTimestamp(),
                "night Hora $i start"
            );
        }
        self::assertSame($nextSunrise->getTimestamp(), $horas[23]->end->getTimestamp());

        // First day Hora is ruled by the weekday lord (Sunday → Sun).
        self::assertSame('Sun', $horas[0]->name);
        // Night Hora 1 continues the unbroken sequence at position 13
        // (index 12 → Sunday's sequence position 5: Jupiter).
        self::assertSame('Jupiter', $horas[12]->name);
    }

    public function testEqualHourVariantIsStillAvailable(): void
    {
        $sunrise = new DateTimeImmutable('2026-06-15 06:00:00', new DateTimeZone('Asia/Kolkata'));
        $sunset = new DateTimeImmutable('2026-06-15 19:00:00', new DateTimeZone('Asia/Kolkata'));
        $nextSunrise = new DateTimeImmutable('2026-06-16 06:00:00', new DateTimeZone('Asia/Kolkata'));

        $horas = $this->calculator->getHoras($sunrise, $sunset, $nextSunrise, 0, HoraMode::EqualHours);

        self::assertCount(24, $horas);
        // 24 equal segments of the full sunrise → next-sunrise span.
        self::assertSame($sunrise->getTimestamp(), $horas[0]->start->getTimestamp());
        self::assertSame($nextSunrise->getTimestamp(), $horas[23]->end->getTimestamp());
        $span = ($nextSunrise->getTimestamp() - $sunrise->getTimestamp()) / 24.0;
        self::assertEqualsWithDelta(
            $span,
            $horas[5]->end->getTimestamp() - $horas[5]->start->getTimestamp(),
            1.0
        );
    }
}
