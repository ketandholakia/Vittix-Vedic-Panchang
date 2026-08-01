<?php

declare(strict_types=1);

namespace Vittix\Panchang\Enum;

/**
 * Standard Parashari Divisional Charts (Vargas).
 */
enum Varga: string
{
    case D1 = 'D1'; // Rasi
    case D2 = 'D2'; // Hora
    case D3 = 'D3'; // Drekkana
    case D9 = 'D9'; // Navamsha
    case D10 = 'D10'; // Dashamsha

    public function nameEnglish(): string
    {
        return match ($this) {
            self::D1 => 'Rasi (D1)',
            self::D2 => 'Hora (D2)',
            self::D3 => 'Drekkana (D3)',
            self::D9 => 'Navamsha (D9)',
            self::D10 => 'Dashamsha (D10)',
        };
    }
}
