<?php

declare(strict_types=1);

namespace Vittix\Panchang\Enum;

/**
 * The 30 Tithis of the lunar month.
 *
 * Index 0–14: Shukla Paksha (Pratipada … Purnima)
 * Index 15–29: Krishna Paksha (Pratipada … Amavasya)
 *
 * A Tithi spans 12° of Moon–Sun elongation.
 */
enum Tithi: int
{
    // Shukla Paksha (waxing)
    case ShuklaPratipada  = 0;
    case ShuklaD2         = 1;
    case ShuklaD3         = 2;
    case ShuklaD4         = 3;
    case ShuklaD5         = 4;
    case ShuklaD6         = 5;
    case ShuklaD7         = 6;
    case ShuklaAshtami    = 7;
    case ShuklaD9         = 8;
    case ShuklaD10        = 9;
    case ShuklaD11        = 10;
    case ShuklaD12        = 11;
    case ShuklaD13        = 12;
    case ShuklaChaturdashi = 13;
    case Purnima          = 14;

    // Krishna Paksha (waning)
    case KrishnaPratipada  = 15;
    case KrishnaD2         = 16;
    case KrishnaD3         = 17;
    case KrishnaD4         = 18;
    case KrishnaD5         = 19;
    case KrishnaD6         = 20;
    case KrishnaD7         = 21;
    case KrishnaAshtami    = 22;
    case KrishnaD9         = 23;
    case KrishnaD10        = 24;
    case KrishnaD11        = 25;
    case KrishnaD12        = 26;
    case KrishnaD13        = 27;
    case KrishnaChaturdashi = 28;
    case Amavasya         = 29;

    public function paksha(): Paksha
    {
        return $this->value <= 14 ? Paksha::Shukla : Paksha::Krishna;
    }

    /**
     * Tithi number within the Paksha (1–15).
     */
    public function number(): int
    {
        return ($this->value % 15) + 1;
    }

    public function nameEnglish(): string
    {
        $shuklaNames = [
            'Pratipada', 'Dwitiya', 'Tritiya', 'Chaturthi', 'Panchami',
            'Shashthi', 'Saptami', 'Ashtami', 'Navami', 'Dashami',
            'Ekadashi', 'Dwadashi', 'Trayodashi', 'Chaturdashi', 'Purnima',
        ];
        $krishnaNames = [
            'Pratipada', 'Dwitiya', 'Tritiya', 'Chaturthi', 'Panchami',
            'Shashthi', 'Saptami', 'Ashtami', 'Navami', 'Dashami',
            'Ekadashi', 'Dwadashi', 'Trayodashi', 'Chaturdashi', 'Amavasya',
        ];

        $idx = $this->value % 15;

        return $this->value <= 14 ? $shuklaNames[$idx] : $krishnaNames[$idx];
    }



    /**
     * Derive from a 0-based elongation index (0–29).
     */
    public static function fromIndex(int $index): self
    {
        return self::from($index);
    }
}
