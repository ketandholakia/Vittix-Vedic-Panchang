<?php

declare(strict_types=1);

namespace Vittix\Panchang\Enum;

/**
 * The 7 Varas (weekdays) as used in Hindu Panchang.
 *
 * Vara is based on the sunrise-to-sunrise day, not the civil midnight-to-midnight day.
 * The integer values match PHP's DateTimeImmutable::format('N') output
 * (ISO weekday: 1 = Monday … 7 = Sunday) for easy mapping.
 */
enum Vara: int
{
    case Ravivara    = 7;  // Sunday    — ruled by Sun
    case Somavara    = 1;  // Monday    — ruled by Moon
    case Mangalavara = 2;  // Tuesday   — ruled by Mars
    case Budhavara   = 3;  // Wednesday — ruled by Mercury
    case Guruvara    = 4;  // Thursday  — ruled by Jupiter
    case Shukravara  = 5;  // Friday    — ruled by Venus
    case Shanivara   = 6;  // Saturday  — ruled by Saturn

    public function nameEnglish(): string
    {
        return match ($this) {
            self::Ravivara    => 'Sunday',
            self::Somavara    => 'Monday',
            self::Mangalavara => 'Tuesday',
            self::Budhavara   => 'Wednesday',
            self::Guruvara    => 'Thursday',
            self::Shukravara  => 'Friday',
            self::Shanivara   => 'Saturday',
        };
    }



    /**
     * Derive Vara from a PHP ISO weekday number (DateTimeImmutable::format('N')).
     */
    public static function fromIsoWeekday(int $isoWeekday): self
    {
        return self::from($isoWeekday);
    }
}
