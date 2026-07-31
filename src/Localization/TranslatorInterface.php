<?php

declare(strict_types=1);

namespace Vittix\Panchang\Localization;

use UnitEnum;

/**
 * Interface for translating Panchang Enum values into localized strings.
 */
interface TranslatorInterface
{
    /**
     * Translate an Enum value into the specified language.
     *
     * @param UnitEnum $enum The Enum value to translate (e.g., Tithi, Nakshatra, Yoga)
     * @param string   $lang The target language code (e.g., 'en', 'hi', 'gu')
     *
     * @return string The translated string, or a fallback (e.g., the enum name) if not found.
     */
    public function translate(UnitEnum $enum, string $lang = 'en'): string;
}
