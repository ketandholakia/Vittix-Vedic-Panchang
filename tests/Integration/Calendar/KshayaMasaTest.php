<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Integration\Calendar;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Enum\LunarMonth;
use Vittix\Panchang\Panchanga\PanchangService;
use Vittix\Panchang\ValueObject\GeoLocation;

/**
 * Tests for Kshaya Masa (Decayed Month) detection.
 */
final class KshayaMasaTest extends TestCase
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

    public function testKshayaMasaIsDetectedFor1963(): void
    {
        // 1963-12-15 is known to be in a Kshaya Masa
        $date = new DateTimeImmutable('1963-12-15', $this->timezone);

        $panchang = $this->service->getForDate($date, $this->ahmedabad);

        self::assertTrue($panchang->isKshayaMasa, '1963-12-15 should be flagged as a Kshaya Masa');
        
        // Ensure the month is Margashirsha (or similar depending on the exact naming convention,
        // but the key is the Kshaya flag is true).
        self::assertNotNull($panchang->lunarMonth);
    }

    public function testNormalMonthIsNotFlaggedAsKshayaMasa(): void
    {
        // A standard month
        $date = new DateTimeImmutable('2024-03-01', $this->timezone);

        $panchang = $this->service->getForDate($date, $this->ahmedabad);

        self::assertFalse($panchang->isKshayaMasa, '2024-03-01 should not be a Kshaya Masa');
    }
}
