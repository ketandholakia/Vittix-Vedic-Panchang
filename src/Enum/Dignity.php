<?php

declare(strict_types=1);

namespace Vittix\Panchang\Enum;

/**
 * Represents the dignity or strength of a planetary body in a given Rashi.
 */
enum Dignity: string
{
    case Exalted = 'exalted';
    case Moolatrikona = 'moolatrikona';
    case OwnSign = 'own_sign';
    case Friendly = 'friendly';
    case Neutral = 'neutral';
    case Enemy = 'enemy';
    case Debilitated = 'debilitated';

    public function nameEnglish(): string
    {
        return match ($this) {
            self::Exalted => 'Exalted',
            self::Moolatrikona => 'Moolatrikona',
            self::OwnSign => 'Own Sign',
            self::Friendly => 'Friendly',
            self::Neutral => 'Neutral',
            self::Enemy => 'Enemy',
            self::Debilitated => 'Debilitated',
        };
    }
}
