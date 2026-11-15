<?php

declare(strict_types=1);

namespace Vittix\Panchang\Festivals;

use Vittix\Panchang\Enum\Kala;
use Vittix\Panchang\Enum\LunarMonth;
use Vittix\Panchang\Enum\Tithi;
use Vittix\Panchang\Festivals\Rules\SunriseTithiRule;
use Vittix\Panchang\Festivals\Rules\ViddhaTithiRule;

/**
 * The default festival catalog.
 *
 * Conventions (documented per festival):
 *
 *   • Month names are Amanta lunations; Adhika (intercalary) lunations are
 *     always skipped, so a festival belongs to the nija month only.
 *   • Sunrise-rules use the classical "tithi at sunrise" preference with
 *     the greater-overlap fallback when the tithi touches no sunrise.
 *   • Nishita windows belong to the civil day whose sunset begins the
 *     night (the puja may fall past midnight — drikpanchang lists
 *     Janmashtami 2026 on Sep 3 with its Nishita puja on Sep 4 early
 *     morning; Sep 4 is the Vaishnava date).
 *   • Diwali follows Amavasya prevailing at Pradosh (not at sunrise),
 *     which is one day earlier in years where Amavasya begins in the
 *     afternoon (2023-11-12, 2025-10-20, 2026-11-08).
 */
final class FestivalCatalog
{
    /**
     * @return Festival[]
     */
    public static function getDefaultFestivals(): array
    {
        return [
            // Chaitra Shukla Pratipada: sunrise rule; in years where
            // Pratipada touches no sunrise (2026: starts 06:54, after the
            // 06:45 sunrise) the greater-overlap fallback resolves the day.
            new Festival(
                'Gudi Padwa / Ugadi',
                new SunriseTithiRule(LunarMonth::Chaitra, Tithi::ShuklaPratipada)
            ),
            // Chaitra Shukla Navami at Madhyahna (drik convention: Rama
            // Navami is observed when Navami prevails at midday).
            new Festival(
                'Rama Navami',
                new ViddhaTithiRule(LunarMonth::Chaitra, Tithi::ShuklaD9, Kala::Madhyahna)
            ),
            // Vaishakha Shukla Tritiya at Madhyahna (2026-04-19: Tritiya
            // ends 07:27 on Apr 20, before that day's Madhyahna).
            new Festival(
                'Akshaya Tritiya',
                new ViddhaTithiRule(LunarMonth::Vaishakha, Tithi::ShuklaD3, Kala::Madhyahna)
            ),
            // Shravana Purnima (nija Shravana; the Adhika Shravana Purnima
            // is skipped — 2023 fires once, not on Aug 1 AND Aug 31).
            new Festival(
                'Raksha Bandhan',
                new SunriseTithiRule(LunarMonth::Shravana, Tithi::Purnima)
            ),
            // Ashwina Amavasya at Pradosh — NOT at sunrise, which is one day
            // late when Amavasya begins in the afternoon (2023, 2025, 2026).
            new Festival(
                'Diwali',
                new ViddhaTithiRule(LunarMonth::Ashwina, Tithi::Amavasya, Kala::Pradosh)
            ),

            // Bhadrapada Shukla Chaturthi at Madhyahna (vyapini).
            new Festival(
                'Ganesha Chaturthi',
                new ViddhaTithiRule(LunarMonth::Bhadrapada, Tithi::ShuklaD4, Kala::Madhyahna)
            ),
            // Shravana Krishna Ashtami during the Nishita of the night
            // beginning the festival day (2026: Ashtami ends 00:14, the
            // Nishita starts ~00:15 — a one-minute overlap must resolve).
            new Festival(
                'Krishna Janmashtami',
                new ViddhaTithiRule(LunarMonth::Shravana, Tithi::KrishnaAshtami, Kala::Nishita)
            ),
            // Magha Krishna Chaturdashi during the Nishita of the night
            // beginning the festival day (2026: Feb 15, puja past midnight).
            new Festival(
                'Maha Shivaratri',
                new ViddhaTithiRule(LunarMonth::Magha, Tithi::KrishnaChaturdashi, Kala::Nishita)
            ),
        ];
    }
}
