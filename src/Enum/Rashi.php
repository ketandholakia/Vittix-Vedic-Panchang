<?php

declare(strict_types=1);

namespace Vittix\Panchang\Enum;

/**
 * The 12 sidereal zodiac signs (Rashi).
 */
enum Rashi: int
{
    case Mesha = 0;
    case Vrishabha = 1;
    case Mithuna = 2;
    case Karka = 3;
    case Simha = 4;
    case Kanya = 5;
    case Tula = 6;
    case Vrischika = 7;
    case Dhanu = 8;
    case Makara = 9;
    case Kumbha = 10;
    case Meena = 11;

    public function nameEnglish(): string
    {
        return match ($this) {
            self::Mesha => 'Mesha',
            self::Vrishabha => 'Vrishabha',
            self::Mithuna => 'Mithuna',
            self::Karka => 'Karka',
            self::Simha => 'Simha',
            self::Kanya => 'Kanya',
            self::Tula => 'Tula',
            self::Vrischika => 'Vrischika',
            self::Dhanu => 'Dhanu',
            self::Makara => 'Makara',
            self::Kumbha => 'Kumbha',
            self::Meena => 'Meena',
        };
    }

    public static function fromIndex(int $index): self
    {
        return self::from($index % 12);
    }
}
