<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Integration\Calendar;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Enum\AyanamshaSystem;
use Vittix\Panchang\Enum\MonthSystem;
use Vittix\Panchang\Panchanga\PanchangService;
use Vittix\Panchang\ValueObject\GeoLocation;
use Vittix\Panchang\Enum\Paksha;
use Vittix\Panchang\Enum\LunarMonth;

final class AmantaVsPurnimantaTest extends TestCase
{
    public function testAmantaVsPurnimantaDuringKrishnaPaksha(): void
    {
        // March 30, 2024 is during Krishna Paksha (waning moon).
        // For Amanta system, this falls in Phalguna.
        // For Purnimanta system, the month changes at the full moon, so it should be Chaitra.
        $date = new DateTimeImmutable('2024-03-30', new DateTimeZone('Asia/Kolkata'));
        $location = new GeoLocation(28.6139, 77.2090, 200); // New Delhi

        $amantaService = PanchangService::createDefault();
        $amantaDay = $amantaService->getForDate($date, $location, MonthSystem::Amanta);
        
        $purnimantaService = PanchangService::createDefault();
        $purnimantaDay = $purnimantaService->getForDate($date, $location, MonthSystem::Purnimanta);

        // Verify it is indeed Krishna Paksha
        self::assertSame(Paksha::Krishna, $amantaDay->paksha, 'Date must be in Krishna Paksha for testing divergence');
        
        // Assert the months are different
        self::assertNotSame(
            $amantaDay->lunarMonth, 
            $purnimantaDay->lunarMonth,
            'Lunar month should differ between Amanta and Purnimanta during Krishna Paksha'
        );

        // Amanta is Phalguna
        self::assertSame(LunarMonth::Phalguna, $amantaDay->lunarMonth);
        // Purnimanta is Chaitra
        self::assertSame(LunarMonth::Chaitra, $purnimantaDay->lunarMonth);
    }

    public function testAmantaVsPurnimantaDuringShuklaPaksha(): void
    {
        // April 15, 2024 is during Shukla Paksha (waxing moon) of Chaitra.
        // For both systems, this should be Chaitra.
        $date = new DateTimeImmutable('2024-04-15', new DateTimeZone('Asia/Kolkata'));
        $location = new GeoLocation(28.6139, 77.2090, 200); // New Delhi

        $amantaService = PanchangService::createDefault();
        $amantaDay = $amantaService->getForDate($date, $location, MonthSystem::Amanta);
        
        $purnimantaService = PanchangService::createDefault();
        $purnimantaDay = $purnimantaService->getForDate($date, $location, MonthSystem::Purnimanta);

        // Verify it is indeed Shukla Paksha
        self::assertSame(Paksha::Shukla, $amantaDay->paksha, 'Date must be in Shukla Paksha for testing convergence');
        
        // Assert the months are the same
        self::assertSame(
            $amantaDay->lunarMonth, 
            $purnimantaDay->lunarMonth,
            'Lunar month should be the same for Amanta and Purnimanta during Shukla Paksha'
        );

        self::assertSame(LunarMonth::Chaitra, $amantaDay->lunarMonth);
    }
}
