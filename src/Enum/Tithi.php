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
 *
 * The deprecated "D<n>" spellings remain as class-constant aliases of
 * the canonical cases (identical instances); cases() lists only the
 * canonical 30.
 */
enum Tithi: int
{
    // Shukla Paksha (waxing) — canonical Sanskrit case names. The numeric
    // value is the 0-based elongation index (value × 12° of Moon−Sun
    // elongation); every index is also usable via from()/fromIndex().
    case ShuklaPratipada  = 0;
    case ShuklaDwitiya    = 1;
    case ShuklaTritiya    = 2;
    case ShuklaChaturthi  = 3;
    case ShuklaPanchami   = 4;
    case ShuklaShashthi   = 5;
    case ShuklaSaptami    = 6;
    case ShuklaAshtami    = 7;
    case ShuklaNavami     = 8;
    case ShuklaDashami    = 9;
    case ShuklaEkadashi   = 10;
    case ShuklaDwadashi   = 11;
    case ShuklaTrayodashi = 12;
    case ShuklaChaturdashi = 13;
    case Purnima          = 14;

    // Krishna Paksha (waning)
    case KrishnaPratipada  = 15;
    case KrishnaDwitiya    = 16;
    case KrishnaTritiya    = 17;
    case KrishnaChaturthi  = 18;
    case KrishnaPanchami   = 19;
    case KrishnaShashthi   = 20;
    case KrishnaSaptami    = 21;
    case KrishnaAshtami    = 22;
    case KrishnaNavami     = 23;
    case KrishnaDashami    = 24;
    case KrishnaEkadashi   = 25;
    case KrishnaDwadashi   = 26;
    case KrishnaTrayodashi = 27;
    case KrishnaChaturdashi = 28;
    case Amavasya         = 29;

    // ------------------------------------------------------------------
    // Deprecated aliases. These class constants hold the canonical enum
    // cases under the old "D<n>" spellings, so existing code (Tithi::ShuklaD3)
    // keeps working with identical object identity; from()/fromIndex() and
    // cases() only ever see the canonical cases above.
    // ------------------------------------------------------------------

    /** @deprecated Use ShuklaDwitiya. */
    public const ShuklaD2 = self::ShuklaDwitiya;
    /** @deprecated Use ShuklaTritiya. */
    public const ShuklaD3 = self::ShuklaTritiya;
    /** @deprecated Use ShuklaChaturthi. */
    public const ShuklaD4 = self::ShuklaChaturthi;
    /** @deprecated Use ShuklaPanchami. */
    public const ShuklaD5 = self::ShuklaPanchami;
    /** @deprecated Use ShuklaShashthi. */
    public const ShuklaD6 = self::ShuklaShashthi;
    /** @deprecated Use ShuklaSaptami. */
    public const ShuklaD7 = self::ShuklaSaptami;
    /** @deprecated Use ShuklaNavami. */
    public const ShuklaD9 = self::ShuklaNavami;
    /** @deprecated Use ShuklaDashami. */
    public const ShuklaD10 = self::ShuklaDashami;
    /** @deprecated Use ShuklaEkadashi. */
    public const ShuklaD11 = self::ShuklaEkadashi;
    /** @deprecated Use ShuklaDwadashi. */
    public const ShuklaD12 = self::ShuklaDwadashi;
    /** @deprecated Use ShuklaTrayodashi. */
    public const ShuklaD13 = self::ShuklaTrayodashi;

    /** @deprecated Use KrishnaDwitiya. */
    public const KrishnaD2 = self::KrishnaDwitiya;
    /** @deprecated Use KrishnaTritiya. */
    public const KrishnaD3 = self::KrishnaTritiya;
    /** @deprecated Use KrishnaChaturthi. */
    public const KrishnaD4 = self::KrishnaChaturthi;
    /** @deprecated Use KrishnaPanchami. */
    public const KrishnaD5 = self::KrishnaPanchami;
    /** @deprecated Use KrishnaShashthi. */
    public const KrishnaD6 = self::KrishnaShashthi;
    /** @deprecated Use KrishnaSaptami. */
    public const KrishnaD7 = self::KrishnaSaptami;
    /** @deprecated Use KrishnaNavami. */
    public const KrishnaD9 = self::KrishnaNavami;
    /** @deprecated Use KrishnaDashami. */
    public const KrishnaD10 = self::KrishnaDashami;
    /** @deprecated Use KrishnaEkadashi. */
    public const KrishnaD11 = self::KrishnaEkadashi;
    /** @deprecated Use KrishnaDwadashi. */
    public const KrishnaD12 = self::KrishnaDwadashi;
    /** @deprecated Use KrishnaTrayodashi. */
    public const KrishnaD13 = self::KrishnaTrayodashi;

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
