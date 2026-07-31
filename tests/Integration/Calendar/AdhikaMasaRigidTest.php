<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Integration\Calendar;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Panchang;
use Vittix\Panchang\ValueObject\GeoLocation;
use Vittix\Panchang\Enum\LunarMonth;

final class AdhikaMasaRigidTest extends TestCase
{
    private Panchang $panchang;
    private GeoLocation $location;

    protected function setUp(): void
    {
        $this->panchang = Panchang::createDefault();
        // Ujjain
        $this->location = new GeoLocation(23.1765, 75.7885);
    }

    public function testAdhikaShravana2023(): void
    {
        $timezone = new DateTimeZone('Asia/Kolkata');
        
        // In 2023, Shravana was an Adhika Masa.
        // It started around July 18 and ended around August 16.
        // Let's check a date in the middle of it, like August 1, 2023.
        $dtAdhika = (new DateTimeImmutable('2023-08-01 12:00:00', $timezone));
        $resultAdhika = $this->panchang->day($dtAdhika, $this->location);
        
        self::assertSame(LunarMonth::Shravana, $resultAdhika->lunarMonth, 'Month should be Shravana');
        self::assertTrue($resultAdhika->isAdhikaMasa, 'Shravana in Aug 2023 should be Adhika (intercalary)');
        
        // Let's check Nija Shravana (the regular one right after)
        // It started around August 17. Let's check August 25, 2023.
        $dtNija = (new DateTimeImmutable('2023-08-25 12:00:00', $timezone));
        $resultNija = $this->panchang->day($dtNija, $this->location);
        
        self::assertSame(LunarMonth::Shravana, $resultNija->lunarMonth, 'Month should be Shravana');
        self::assertFalse($resultNija->isAdhikaMasa, 'Shravana in late Aug 2023 should be Nija (regular)');
    }
}
