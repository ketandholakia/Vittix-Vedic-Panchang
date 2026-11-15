<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Unit\Calendar;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Astronomy\Engine\EngineFactory;
use Vittix\Panchang\Calendar\LunarMonthCalculator;
use Vittix\Panchang\Calendar\SamvatCalculator;
use Vittix\Panchang\Tests\Support\TestLocations;
use Vittix\Panchang\Time\AstroTimeFactory;
use Vittix\Panchang\ValueObject\GeoLocation;

/**
 * Exact Samvat years anchored to the lunar calendar.
 *
 *   • Vikram and Shaka change at Chaitra Shukla Pratipada (Yugadi /
 *     Gudi Padwa), NOT on a fixed Gregorian date.
 *   • Gujarati Vikram changes at Kartika Shukla Pratipada (the day after
 *     Diwali), NOT on November 1.
 *
 * Reference boundaries: Yugadi 2025 = 2025-03-30, Gujarati New Year
 * 2025 = 2025-10-22 (Diwali 2025-10-21).
 */
final class SamvatCalculatorExactTest extends TestCase
{
    private SamvatCalculator $calc;

    private GeoLocation $location;

    private DateTimeZone $timezone;

    protected function setUp(): void
    {
        $engine = EngineFactory::createPurePhpEngine();
        $lunarMonth = new LunarMonthCalculator($engine);
        $this->calc = new SamvatCalculator($lunarMonth, $engine);
        $this->location = TestLocations::ahmedabad();
        $this->timezone = new DateTimeZone('Asia/Kolkata');
    }

    private function instant(string $dateTime): \Vittix\Panchang\ValueObject\AstroInstant
    {
        return (new AstroTimeFactory())->fromDateTime(
            new DateTimeImmutable($dateTime, $this->timezone)
        );
    }

    // -----------------------------------------------------------------------
    // Vikram Samvat — Chaitra Shukla Pratipada
    // -----------------------------------------------------------------------

    public function testVikramChangesAtChaitraShuklaPratipada(): void
    {
        // Yugadi 2025 fell on March 30.
        self::assertSame(2081, $this->calc->vikramSamvatExact($this->instant('2025-03-29 12:00'), $this->location, $this->timezone));
        self::assertSame(2082, $this->calc->vikramSamvatExact($this->instant('2025-03-31 12:00'), $this->location, $this->timezone));
        // On the new-year day itself: before sunrise still the old year.
        self::assertSame(2081, $this->calc->vikramSamvatExact($this->instant('2025-03-30 04:00'), $this->location, $this->timezone));
        self::assertSame(2082, $this->calc->vikramSamvatExact($this->instant('2025-03-30 12:00'), $this->location, $this->timezone));
    }

    public function testVikramExactMatchesApproximationAwayFromBoundary(): void
    {
        // Well after the new year both must agree.
        $instant = $this->instant('2025-06-15 12:00');
        self::assertSame(
            $this->calc->vikramSamvat(new DateTimeImmutable('2025-06-15', $this->timezone)),
            $this->calc->vikramSamvatExact($instant, $this->location, $this->timezone)
        );
        // Well before the new year both must agree.
        $instant = $this->instant('2026-01-15 12:00');
        self::assertSame(
            $this->calc->vikramSamvat(new DateTimeImmutable('2026-01-15', $this->timezone)),
            $this->calc->vikramSamvatExact($instant, $this->location, $this->timezone)
        );
    }

    // -----------------------------------------------------------------------
    // Shaka Samvat — Chaitra Shukla Pratipada
    // -----------------------------------------------------------------------

    public function testShakaChangesAtChaitraShuklaPratipada(): void
    {
        self::assertSame(1946, $this->calc->shakaSamvatExact($this->instant('2025-03-01 12:00'), $this->location, $this->timezone));
        self::assertSame(1947, $this->calc->shakaSamvatExact($this->instant('2025-04-15 12:00'), $this->location, $this->timezone));
        // The Shaka year must track the same lunar boundary as Vikram.
        self::assertSame(1946, $this->calc->shakaSamvatExact($this->instant('2025-03-29 12:00'), $this->location, $this->timezone));
        self::assertSame(1947, $this->calc->shakaSamvatExact($this->instant('2025-03-31 12:00'), $this->location, $this->timezone));
    }

    // -----------------------------------------------------------------------
    // Gujarati Vikram Samvat — Kartika Shukla Pratipada
    // -----------------------------------------------------------------------

    public function testGujaratiChangesAtKartikaShuklaPratipada(): void
    {
        // Diwali 2025-10-21, Gujarati New Year 2025-10-22.
        self::assertSame(2081, $this->calc->gujaratiSamvatExact($this->instant('2025-10-21 12:00'), $this->location, $this->timezone));
        self::assertSame(2082, $this->calc->gujaratiSamvatExact($this->instant('2025-10-25 12:00'), $this->location, $this->timezone));
        // On the new-year day: before sunrise old year, after sunrise new.
        self::assertSame(2081, $this->calc->gujaratiSamvatExact($this->instant('2025-10-22 04:00'), $this->location, $this->timezone));
        self::assertSame(2082, $this->calc->gujaratiSamvatExact($this->instant('2025-10-22 12:00'), $this->location, $this->timezone));
    }

    public function testGujaratiLagsVikramUntilKartikaPratipada(): void
    {
        // Between Chaitra Shukla Pratipada (Mar 30) and Kartika Shukla
        // Pratipada (Oct 22) the Gujarati year is one behind Vikram.
        $instant = $this->instant('2025-06-15 12:00');
        self::assertSame(2082, $this->calc->vikramSamvatExact($instant, $this->location, $this->timezone));
        self::assertSame(2081, $this->calc->gujaratiSamvatExact($instant, $this->location, $this->timezone));

        // From Kartika Shukla Pratipada the Gujarati year has caught up to
        // the number the Vikram year reached at the previous Chaitra.
        $instant = $this->instant('2025-11-15 12:00');
        self::assertSame(
            $this->calc->vikramSamvatExact($instant, $this->location, $this->timezone),
            $this->calc->gujaratiSamvatExact($instant, $this->location, $this->timezone)
        );
    }
}
