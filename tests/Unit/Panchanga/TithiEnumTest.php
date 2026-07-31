<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Unit\Panchanga;

use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Enum\Paksha;
use Vittix\Panchang\Enum\Tithi;

final class TithiEnumTest extends TestCase
{
    /** @dataProvider allTithiMappings */
    public function testEveryTithiMapping(
        int $index,
        Paksha $expectedPaksha,
        int $expectedNumber,
        string $expectedName
    ): void {
        $tithi = Tithi::fromIndex($index);

        self::assertSame($expectedPaksha, $tithi->paksha());
        self::assertSame($expectedNumber, $tithi->number());
        self::assertSame($expectedName, $tithi->nameEnglish());
    }

    /**
     * @return array<string, array{int, Paksha, int, string}>
     */
    public static function allTithiMappings(): array
    {
        $names = [
            'Pratipada',
            'Dwitiya',
            'Tritiya',
            'Chaturthi',
            'Panchami',
            'Shashthi',
            'Saptami',
            'Ashtami',
            'Navami',
            'Dashami',
            'Ekadashi',
            'Dwadashi',
            'Trayodashi',
            'Chaturdashi',
        ];

        $cases = [];

        foreach ($names as $offset => $name) {
            $number = $offset + 1;

            $cases["Shukla {$name}"] = [
                $offset,
                Paksha::Shukla,
                $number,
                $name,
            ];

            $cases["Krishna {$name}"] = [
                $offset + 15,
                Paksha::Krishna,
                $number,
                $name,
            ];
        }

        $cases['Purnima'] = [
            14,
            Paksha::Shukla,
            15,
            'Purnima',
        ];

        $cases['Amavasya'] = [
            29,
            Paksha::Krishna,
            15,
            'Amavasya',
        ];

        return $cases;
    }
}
