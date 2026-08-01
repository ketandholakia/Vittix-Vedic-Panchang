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
    case D4 = 'D4'; // Chaturthamsha
    case D7 = 'D7'; // Saptamsha
    case D9 = 'D9'; // Navamsha
    case D10 = 'D10'; // Dashamsha
    case D12 = 'D12'; // Dwadashamsha
    case D16 = 'D16'; // Shodashamsha
    case D20 = 'D20'; // Vimshamsha
    case D24 = 'D24'; // Chaturvimshamsha
    case D27 = 'D27'; // Saptavimshamsha
    case D30 = 'D30'; // Trimshamsha
    case D40 = 'D40'; // Khavedamsha
    case D45 = 'D45'; // Akshavedamsha
    case D60 = 'D60'; // Shashtiamsha

    public function nameEnglish(): string
    {
        return match ($this) {
            self::D1 => 'Rasi (D1)',
            self::D2 => 'Hora (D2)',
            self::D3 => 'Drekkana (D3)',
            self::D4 => 'Chaturthamsha (D4)',
            self::D7 => 'Saptamsha (D7)',
            self::D9 => 'Navamsha (D9)',
            self::D10 => 'Dashamsha (D10)',
            self::D12 => 'Dwadashamsha (D12)',
            self::D16 => 'Shodashamsha (D16)',
            self::D20 => 'Vimshamsha (D20)',
            self::D24 => 'Chaturvimshamsha (D24)',
            self::D27 => 'Saptavimshamsha (D27)',
            self::D30 => 'Trimshamsha (D30)',
            self::D40 => 'Khavedamsha (D40)',
            self::D45 => 'Akshavedamsha (D45)',
            self::D60 => 'Shashtiamsha (D60)',
        };
    }
}
