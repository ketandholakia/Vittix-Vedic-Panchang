<?php

declare(strict_types=1);

namespace Vittix\Panchang\Festivals;

use Vittix\Panchang\Enum\LunarMonth;
use Vittix\Panchang\Enum\Tithi;
use Vittix\Panchang\Festivals\Rules\SunriseTithiRule;
use Vittix\Panchang\Festivals\Rules\ViddhaTithiRule;
use Vittix\Panchang\Muhurta\MuhurtaCalculator;

final class FestivalCatalog
{
    /**
     * @return Festival[]
     */
    public static function getDefaultFestivals(MuhurtaCalculator $muhurtaCalculator): array
    {
        return [
            // Standard Sunrise-based Festivals
            new Festival(
                'Gudi Padwa / Ugadi',
                new SunriseTithiRule(LunarMonth::Chaitra, Tithi::ShuklaPratipada)
            ),
            new Festival(
                'Rama Navami',
                new SunriseTithiRule(LunarMonth::Chaitra, Tithi::ShuklaD9)
            ),
            new Festival(
                'Akshaya Tritiya',
                new SunriseTithiRule(LunarMonth::Vaishakha, Tithi::ShuklaD3)
            ),
            new Festival(
                'Raksha Bandhan',
                new SunriseTithiRule(LunarMonth::Shravana, Tithi::Purnima)
            ),
            new Festival(
                'Diwali',
                new SunriseTithiRule(LunarMonth::Ashvina, Tithi::Amavasya) // Typically Ashvina or Kartika depending on convention, we use Amanta Ashvina here
            ),

            // Time-specific (Viddha) Festivals
            new Festival(
                'Ganesha Chaturthi',
                new ViddhaTithiRule(LunarMonth::Bhadrapada, Tithi::ShuklaD4, 'Madhyahna', $muhurtaCalculator)
            ),
            new Festival(
                'Krishna Janmashtami',
                new ViddhaTithiRule(LunarMonth::Shravana, Tithi::KrishnaAshtami, 'Nishita', $muhurtaCalculator)
            ),
            new Festival(
                'Maha Shivaratri',
                new ViddhaTithiRule(LunarMonth::Magha, Tithi::KrishnaChaturdashi, 'Nishita', $muhurtaCalculator)
            ),
        ];
    }
}
