<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Unit\Enum;

use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Enum\SolarMonth;

final class SolarMonthTest extends TestCase
{
    /**
     * @dataProvider siderealLongitudeProvider
     */
    public function testFromSiderealLongitude(float $longitude, SolarMonth $expected): void
    {
        self::assertSame($expected, SolarMonth::fromSiderealLongitude($longitude));
    }

    /**
     * @return array<string, array{0: float, 1: SolarMonth}>
     */
    public static function siderealLongitudeProvider(): array
    {
        return [
            'Mesha 0°'      => [0.0, SolarMonth::Mesha],
            'Mesha 15°'     => [15.0, SolarMonth::Mesha],
            'Mesha 29.99°'  => [29.99, SolarMonth::Mesha],
            'Vrishabha 30°' => [30.0, SolarMonth::Vrishabha],
            'Mithuna 60°'   => [60.0, SolarMonth::Mithuna],
            'Karka 90°'     => [90.0, SolarMonth::Karka],
            'Simha 120°'    => [120.0, SolarMonth::Simha],
            'Kanya 150°'    => [150.0, SolarMonth::Kanya],
            'Tula 180°'     => [180.0, SolarMonth::Tula],
            'Vrischika 210°' => [210.0, SolarMonth::Vrischika],
            'Dhanu 240°'    => [240.0, SolarMonth::Dhanu],
            'Makara 270°'   => [270.0, SolarMonth::Makara],
            'Kumbha 300°'   => [300.0, SolarMonth::Kumbha],
            'Meena 330°'    => [330.0, SolarMonth::Meena],
            'Meena 359.99°' => [359.99, SolarMonth::Meena],
        ];
    }

    public function testAllMonthsHaveEnglishAndWesternNames(): void
    {
        foreach (SolarMonth::cases() as $month) {
            self::assertNotEmpty($month->nameEnglish());
            self::assertNotEmpty($month->westernName());
        }
    }

    public function testMonthCount(): void
    {
        self::assertCount(12, SolarMonth::cases());
    }
}
