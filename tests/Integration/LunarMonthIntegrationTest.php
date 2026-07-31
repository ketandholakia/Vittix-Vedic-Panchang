<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Integration;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Enum\LunarMonth;
use Vittix\Panchang\Panchang;
use Vittix\Panchang\Tests\Support\TestLocations;
use Vittix\Panchang\ValueObject\GeoLocation;

/**
 * Integration tests for lunar month names.
 *
 * Reference dates and expected Amanta month names verified against:
 *  - drikpanchang.com (primary reference)
 *  - panchang.com (secondary reference)
 *
 * The engine uses sidereal solar longitude at new moon to derive the month
 * name, so accuracy within ±1 day of the expected value is acceptable.
 */
final class LunarMonthIntegrationTest extends TestCase
{
    private Panchang $panchang;
    private GeoLocation $ahmedabad;

    protected function setUp(): void
    {
        $this->panchang  = Panchang::createDefault();
        $this->ahmedabad = TestLocations::ahmedabad();
    }

    // -----------------------------------------------------------------------
    // Structural tests — non-null and valid range
    // -----------------------------------------------------------------------

    public function testLunarMonthIsNotNullForAhmedabad_2026July(): void
    {
        $date = new DateTimeImmutable('2026-07-31', new DateTimeZone('Asia/Kolkata'));
        $day  = $this->panchang->day($date, $this->ahmedabad);

        self::assertNotNull(
            $day->lunarMonth,
            'lunarMonth must be populated after Milestone 8'
        );
    }

    public function testLunarMonthValueIsInRange(): void
    {
        $date = new DateTimeImmutable('2026-07-31', new DateTimeZone('Asia/Kolkata'));
        $day  = $this->panchang->day($date, $this->ahmedabad);

        if ($day->lunarMonth !== null) {
            self::assertGreaterThanOrEqual(1, $day->lunarMonth->value);
            self::assertLessThanOrEqual(12, $day->lunarMonth->value);
        }
    }

    // -----------------------------------------------------------------------
    // Named month assertions for known dates
    // -----------------------------------------------------------------------

    public function testLunarMonth_2026July31_IsAshadha(): void
    {
        $date = new DateTimeImmutable('2026-07-31', new DateTimeZone('Asia/Kolkata'));
        $day  = $this->panchang->day($date, $this->ahmedabad);

        self::assertSame(LunarMonth::Ashadha, $day->lunarMonth);
    }

    public function testLunarMonth_2026Jan14_IsPausha(): void
    {
        $date = new DateTimeImmutable('2026-01-14', new DateTimeZone('Asia/Kolkata'));
        $day  = $this->panchang->day($date, $this->ahmedabad);

        self::assertSame(LunarMonth::Pausha, $day->lunarMonth);
    }

    public function testLunarMonth_2026Mar30_IsChaitra(): void
    {
        $date = new DateTimeImmutable('2026-03-30', new DateTimeZone('Asia/Kolkata'));
        $day  = $this->panchang->day($date, $this->ahmedabad);

        self::assertSame(LunarMonth::Chaitra, $day->lunarMonth);
    }

    // -----------------------------------------------------------------------
    // Adhika Masa
    // -----------------------------------------------------------------------

    public function testAdhikaMasa_2023(): void
    {
        // 2023-08-05 was Adhika Shravana
        $dateAdhika = new DateTimeImmutable('2023-08-05', new DateTimeZone('Asia/Kolkata'));
        $dayAdhika  = $this->panchang->day($dateAdhika, $this->ahmedabad);
        
        self::assertSame(LunarMonth::Shravana, $dayAdhika->lunarMonth);
        self::assertTrue($dayAdhika->isAdhikaMasa);

        // 2026-07-31 is Nija (regular) Ashadha
        $dateRegular = new DateTimeImmutable('2026-07-31', new DateTimeZone('Asia/Kolkata'));
        $dayRegular  = $this->panchang->day($dateRegular, $this->ahmedabad);
        
        self::assertSame(LunarMonth::Ashadha, $dayRegular->lunarMonth);
        self::assertFalse($dayRegular->isAdhikaMasa);
    }

    // -----------------------------------------------------------------------
    // Samvat years on the PanchangDay (via calendar.php or via Samvat calc)
    // -----------------------------------------------------------------------

    public function testPanchangDayHasValidPaksha(): void
    {
        $date = new DateTimeImmutable('2026-07-31', new DateTimeZone('Asia/Kolkata'));
        $day  = $this->panchang->day($date, $this->ahmedabad);

        self::assertNotNull($day->paksha);
    }
}
