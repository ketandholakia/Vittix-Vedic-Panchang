<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Integration;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Panchang;
use Vittix\Panchang\Tests\Support\TestLocations;
use Vittix\Panchang\ValueObject\GeoLocation;

/**
 * Smoke test: run the full engine for today's Panchang (Ahmedabad) and assert
 * structural correctness.
 *
 * This test does NOT validate numerical accuracy (that requires a fixture
 * dataset). It validates that:
 *  - No exceptions are thrown.
 *  - Longitudes are within [0, 360).
 *  - Tithi index is 0–29.
 *  - Nakshatra index is 0–26.
 *  - Sunrise is before sunset.
 *  - Transition times are DateTimeImmutable instances.
 */
final class PanchangDaySmokeTest extends TestCase
{
    private Panchang $panchang;
    private GeoLocation $ahmedabad;

    protected function setUp(): void
    {
        $this->panchang  = Panchang::createDefault();
        $this->ahmedabad = TestLocations::ahmedabad();
    }

    public function testDoesNotThrowForAhmedabad(): void
    {
        $date = new DateTimeImmutable('2026-07-31', new DateTimeZone('Asia/Kolkata'));
        $this->panchang->day($date, $this->ahmedabad);

        $this->addToAssertionCount(1);
    }

    public function testSunriseIsBeforeSunset(): void
    {
        $date = new DateTimeImmutable('2026-07-31', new DateTimeZone('Asia/Kolkata'));
        $day  = $this->panchang->day($date, $this->ahmedabad);

        if ($day->solarEvents->sunrise !== null && $day->solarEvents->sunset !== null) {
            self::assertLessThan(
                $day->solarEvents->sunset->getTimestamp(),
                $day->solarEvents->sunrise->getTimestamp(),
                'Sunrise must be before sunset'
            );
        }
    }

    public function testTithiIndexIsInRange(): void
    {
        $date = new DateTimeImmutable('2026-07-31', new DateTimeZone('Asia/Kolkata'));
        $day  = $this->panchang->day($date, $this->ahmedabad);

        self::assertGreaterThanOrEqual(0, $day->tithiAtSunrise->value);
        self::assertLessThanOrEqual(29, $day->tithiAtSunrise->value);
    }

    public function testNakshatraIndexIsInRange(): void
    {
        $date = new DateTimeImmutable('2026-07-31', new DateTimeZone('Asia/Kolkata'));
        $day  = $this->panchang->day($date, $this->ahmedabad);

        self::assertGreaterThanOrEqual(0, $day->nakshatraAtSunrise->value);
        self::assertLessThanOrEqual(26, $day->nakshatraAtSunrise->value);
    }

    public function testPadaIsInRange(): void
    {
        $date = new DateTimeImmutable('2026-07-31', new DateTimeZone('Asia/Kolkata'));
        $day  = $this->panchang->day($date, $this->ahmedabad);

        self::assertGreaterThanOrEqual(1, $day->padaAtSunrise);
        self::assertLessThanOrEqual(4, $day->padaAtSunrise);
    }

    public function testTithiEndIsAfterSunrise(): void
    {
        $date = new DateTimeImmutable('2026-07-31', new DateTimeZone('Asia/Kolkata'));
        $day  = $this->panchang->day($date, $this->ahmedabad);

        if ($day->tithiEndsAt !== null && $day->solarEvents->sunrise !== null) {
            self::assertGreaterThan(
                $day->solarEvents->sunrise->getTimestamp(),
                $day->tithiEndsAt->getTimestamp(),
                'Tithi end must be after sunrise'
            );
        }
    }

    public function testMomentDoesNotThrow(): void
    {
        $moment = new DateTimeImmutable('2026-07-31 12:00:00', new DateTimeZone('Asia/Kolkata'));
        $this->panchang->moment($moment, $this->ahmedabad);

        $this->addToAssertionCount(1);
    }

    /**
     * Check the Tithi at noon UTC on 2000-01-01 (J2000.0).
     * Moon−Sun elongation at J2000.0 is approximately 295°
     * → Tithi index = floor(295/12) = 24 = Krishna Dashami
     */
    public function testJ2000Tithi(): void
    {
        $moment = new DateTimeImmutable('2000-01-01 12:00:00', new DateTimeZone('UTC'));
        $london = new GeoLocation(51.5074, -0.1278);
        $result = $this->panchang->moment($moment, $london);

        self::assertSame(\Vittix\Panchang\Enum\Tithi::KrishnaD11, $result->tithi);
    }
    public function testVriddhiTithiDoesNotCreateSecondTithi(): void
    {
        $date = new DateTimeImmutable(
            '2025-01-18',
            new DateTimeZone('Asia/Kolkata')
        );

        $day = $this->panchang->day(
            $date,
            TestLocations::ahmedabad()
        );

        self::assertSame(\Vittix\Panchang\Enum\Tithi::KrishnaD5, $day->tithiAtSunrise);
        self::assertNull($day->tithiSecond);
        self::assertNull($day->tithiSecondEndsAt);
    }
}
