<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Integration\Calendar;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Panchang;
use Vittix\Panchang\ValueObject\GeoLocation;
use Vittix\Panchang\Enum\LunarMonth;

final class AdhikaMasaBoundaryTest extends TestCase
{
    private Panchang $panchang;
    private GeoLocation $location;
    private DateTimeZone $tz;

    protected function setUp(): void
    {
        $this->panchang = Panchang::createDefault();
        // Ujjain
        $this->location = new GeoLocation(23.1765, 75.7885);
        $this->tz = new DateTimeZone('Asia/Kolkata');
    }

    public function testShravanaAdhika2023Transitions(): void
    {
        // July 17, 2023: Last day of Ashadha
        $july17 = new DateTimeImmutable('2023-07-17', $this->tz);
        $resultJuly17 = $this->panchang->day($july17, $this->location);
        self::assertSame(LunarMonth::Ashadha, $resultJuly17->lunarMonth, 'July 17 should be Ashadha');
        self::assertFalse($resultJuly17->isAdhikaMasa, 'July 17 Ashadha is not Adhika');

        // July 18, 2023: First day of Adhika Shravana
        $july18 = new DateTimeImmutable('2023-07-18', $this->tz);
        $resultJuly18 = $this->panchang->day($july18, $this->location);
        self::assertSame(LunarMonth::Shravana, $resultJuly18->lunarMonth, 'July 18 should be Shravana');
        self::assertTrue($resultJuly18->isAdhikaMasa, 'July 18 Shravana is Adhika');

        // August 16, 2023: Last day of Adhika Shravana
        $aug16 = new DateTimeImmutable('2023-08-16', $this->tz);
        $resultAug16 = $this->panchang->day($aug16, $this->location);
        self::assertSame(LunarMonth::Shravana, $resultAug16->lunarMonth, 'August 16 should be Shravana');
        self::assertTrue($resultAug16->isAdhikaMasa, 'August 16 Shravana is Adhika');

        // August 17, 2023: First day of Nija Shravana
        $aug17 = new DateTimeImmutable('2023-08-17', $this->tz);
        $resultAug17 = $this->panchang->day($aug17, $this->location);
        self::assertSame(LunarMonth::Shravana, $resultAug17->lunarMonth, 'August 17 should be Shravana');
        self::assertFalse($resultAug17->isAdhikaMasa, 'August 17 Shravana is Nija (regular)');
    }

    public function testFutureAdhikaMasa2042(): void
    {
        // 2042 has an Adhika Masa in Shravana. 
        // Let's verify a date in middle of it.
        $dtAdhika = new DateTimeImmutable('2042-08-15', $this->tz);
        $resultAdhika = $this->panchang->day($dtAdhika, $this->location);
        
        self::assertSame(LunarMonth::Shravana, $resultAdhika->lunarMonth, 'Month should be Shravana');
        self::assertTrue($resultAdhika->isAdhikaMasa, 'Shravana in Aug 2042 should be Adhika');
    }
}
