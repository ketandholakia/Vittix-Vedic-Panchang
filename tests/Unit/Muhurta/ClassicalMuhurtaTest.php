<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Unit\Muhurta;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Muhurta\ClassicalMuhurta;
use Vittix\Panchang\Muhurta\MuhurtaCalculator;

final class ClassicalMuhurtaTest extends TestCase
{
    public function testRahuKaal(): void
    {
        $calculator = new MuhurtaCalculator();
        $classical = new ClassicalMuhurta($calculator);

        // A sample day with exactly 12 hours of daylight for easy math.
        $tz = new DateTimeZone('Asia/Kolkata');
        $sunrise = new DateTimeImmutable('2026-07-31 06:00:00', $tz);
        $sunset = new DateTimeImmutable('2026-07-31 18:00:00', $tz);
        
        // 2026-07-31 is a Friday.
        // Friday Rahu Kaal is the 4th segment (out of 8).
        // Total day is 12 hours. Each segment is 1.5 hours.
        // 1st: 06:00 - 07:30
        // 2nd: 07:30 - 09:00
        // 3rd: 09:00 - 10:30
        // 4th: 10:30 - 12:00
        
        $rahu = $classical->getRahuKaal($sunrise, $sunset, 5); // 5 = Friday
        
        self::assertSame('Rahu Kaal', $rahu->name);
        self::assertSame('10:30:00', $rahu->start->format('H:i:s'));
        self::assertSame('12:00:00', $rahu->end->format('H:i:s'));
    }

    public function testAbhijitMuhurta(): void
    {
        $calculator = new MuhurtaCalculator();
        $classical = new ClassicalMuhurta($calculator);

        $tz = new DateTimeZone('Asia/Kolkata');
        $sunrise = new DateTimeImmutable('2026-07-31 06:00:00', $tz);
        $sunset = new DateTimeImmutable('2026-07-31 18:00:00', $tz);
        
        // Abhijit is the 8th Muhurta (out of 15).
        // Day is 12 hours = 720 minutes. Each Muhurta is 48 minutes.
        // 8th Muhurta starts after 7 * 48 = 336 minutes = 5 hours 36 minutes.
        // Starts at 11:36:00. Ends at 12:24:00.
        
        $abhijit = $classical->getAbhijit($sunrise, $sunset);
        
        self::assertSame('Vidhi', $abhijit->name); // Name of the 8th muhurta
        self::assertSame('11:36:00', $abhijit->start->format('H:i:s'));
        self::assertSame('12:24:00', $abhijit->end->format('H:i:s'));
    }

    public function testChoghadiya(): void
    {
        $calculator = new MuhurtaCalculator();

        $tz = new DateTimeZone('Asia/Kolkata');
        $sunrise = new DateTimeImmutable('2026-07-31 06:00:00', $tz);
        $sunset = new DateTimeImmutable('2026-07-31 18:00:00', $tz);
        
        // 2026-07-31 is a Friday (5).
        $choghadiya = $calculator->getDayChoghadiya($sunrise, $sunset, 5);
        
        self::assertCount(8, $choghadiya);
        // Friday sequence: Chal, Labh, Amrit, Kaal, Shubh, Rog, Udveg, Chal
        self::assertSame('Chal', $choghadiya[0]->name);
        self::assertSame('Amrit', $choghadiya[2]->name);
        self::assertSame('Chal', $choghadiya[7]->name);
        
        // 1.5 hours per choghadiya
        self::assertSame('07:30:00', $choghadiya[0]->end->format('H:i:s'));
    }
}
