<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Integration\Calendar;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Enum\LunarMonth;
use Vittix\Panchang\Enum\MonthSystem;
use Vittix\Panchang\Panchanga\PanchangService;
use Vittix\Panchang\ValueObject\GeoLocation;

/**
 * Purnimanta months around Adhika (intercalary) months.
 *
 * A Purnimanta month runs from full moon to full moon and is named after
 * the Amanta lunation that follows its starting full moon; its Adhika
 * status follows that same lunation.
 *
 * Reference year 2023 (Adhika Shravana, Amanta 2023-07-18 … 2023-08-16):
 *   • 2023-07-25 (Shukla of the Adhika lunation)  → Shravana, Adhika.
 *   • 2023-08-05 / 08-10 (Krishna of the Adhika
 *     lunation, i.e. the fortnight before nija Shravana's
 *     Amanta lunation begins)                    → Shravana (nija).
 *   • 2023-07-10 (Krishna of the lunation before
 *     the Adhika one)                            → Adhika Shravana.
 */
final class PurnimantaAdhikaTest extends TestCase
{
    private PanchangService $service;
    private GeoLocation $ahmedabad;
    private DateTimeZone $timezone;

    protected function setUp(): void
    {
        $this->service = PanchangService::createDefault();
        $this->ahmedabad = new GeoLocation(23.0225, 72.5714, 53.0);
        $this->timezone = new DateTimeZone('Asia/Kolkata');
    }

    private function purnimantaDay(string $date): \Vittix\Panchang\ValueObject\PanchangResult
    {
        return $this->service->getForDate(
            new DateTimeImmutable($date, $this->timezone),
            $this->ahmedabad,
            MonthSystem::Purnimanta
        );
    }

    public function testShuklaOfAdhikaLunationIsAdhikaShravana(): void
    {
        $day = $this->purnimantaDay('2023-07-25');

        self::assertSame(LunarMonth::Shravana, $day->lunarMonth);
        self::assertTrue($day->isAdhikaMasa, '2023-07-25 lies in the Adhika Shravana Purnimanta month');
    }

    public function testKrishnaOfAdhikaLunationIsNijaShravana(): void
    {
        // The Krishna Paksha of the Adhika Amanta lunation belongs (Purnimanta)
        // to the month named after the FOLLOWING lunation — nija Shravana.
        foreach (['2023-08-05', '2023-08-10'] as $date) {
            $day = $this->purnimantaDay($date);

            self::assertSame(
                LunarMonth::Shravana,
                $day->lunarMonth,
                "$date must be Purnimanta nija Shravana, not Adhika and not Bhadrapada"
            );
            self::assertFalse(
                $day->isAdhikaMasa,
                "$date is in the nija Shravana Purnimanta month (regular, not intercalary)"
            );
        }
    }

    public function testKrishnaBeforeAdhikaLunationIsAdhikaShravana(): void
    {
        // 2023-07-10 is Krishna Paksha of the Ashadha Amanta lunation, but in
        // Purnimanta reckoning it opens the month named after the next lunation:
        // Adhika Shravana.
        $day = $this->purnimantaDay('2023-07-10');

        self::assertSame(LunarMonth::Shravana, $day->lunarMonth);
        self::assertTrue($day->isAdhikaMasa, '2023-07-10 opens the Adhika Shravana Purnimanta month');
    }

    public function testNormalMonthShuklaAndKrishnaAgreeWithAmantaPlusOne(): void
    {
        // 2023-09-20 is Shukla Paksha of the Bhadrapada Amanta lunation:
        // Purnimanta Bhadrapada, regular.
        $shukla = $this->purnimantaDay('2023-09-20');
        self::assertSame(LunarMonth::Bhadrapada, $shukla->lunarMonth);
        self::assertFalse($shukla->isAdhikaMasa);

        // 2023-09-08 is Krishna Paksha (between full moons Sep 29 & Aug 31):
        // Purnimanta Bhadrapada as well (the month that ends at Bhadrapada
        // Purnima).
        $krishna = $this->purnimantaDay('2023-09-08');
        self::assertSame(LunarMonth::Bhadrapada, $krishna->lunarMonth);
        self::assertFalse($krishna->isAdhikaMasa);
    }

    public function testKshayaMonthIsNamedAfterFollowingLunation(): void
    {
        // 1963-12-15 lies in the Kshaya lunation (two Sankrantis, Nov 14 →
        // Dec 16). 1963-12-15 is Krishna Paksha of that lunation, so the
        // Purnimanta month is named after the Amanta lunation that follows
        // the full moon — the lunation starting 1963-12-16, which is the
        // month AFTER the kshaya lunation's name: Amanta+1, not Adhika.
        $amanta = $this->service->getForDate(
            new DateTimeImmutable('1963-12-15', $this->timezone),
            $this->ahmedabad
        );
        $purnimanta = $this->purnimantaDay('1963-12-15');

        self::assertTrue($amanta->isKshayaMasa, 'precondition: 1963-12-15 is in a Kshaya lunation');
        self::assertNotNull($amanta->lunarMonth);
        self::assertNotNull($purnimanta->lunarMonth);
        self::assertSame(
            LunarMonth::from(($amanta->lunarMonth->value % 12) + 1),
            $purnimanta->lunarMonth,
            'Purnimanta Krishna Paksha of a Kshaya lunation is named after the following lunation'
        );
        self::assertFalse($purnimanta->isAdhikaMasa);
    }
}
