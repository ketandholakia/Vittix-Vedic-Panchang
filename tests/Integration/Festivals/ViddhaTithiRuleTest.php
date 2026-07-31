<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Integration\Festivals;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Astronomy\Engine\PurePhpAstronomyEngine;
use Vittix\Panchang\Enum\LunarMonth;
use Vittix\Panchang\Enum\Tithi;
use Vittix\Panchang\Festivals\Festival;
use Vittix\Panchang\Festivals\FestivalEngine;
use Vittix\Panchang\Festivals\Rules\ViddhaTithiRule;
use Vittix\Panchang\Muhurta\MuhurtaCalculator;
use Vittix\Panchang\Panchanga\KaranaCalculator;
use Vittix\Panchang\Panchanga\NakshatraCalculator;
use Vittix\Panchang\Panchanga\PanchangaCalculator;
use Vittix\Panchang\Panchanga\TithiCalculator;
use Vittix\Panchang\Panchanga\TransitionFinder;
use Vittix\Panchang\Panchanga\VaraCalculator;
use Vittix\Panchang\Panchanga\YogaCalculator;
use Vittix\Panchang\Calendar\LunarMonthCalculator;
use Vittix\Panchang\Calendar\AdhikaMasaCalculator;
use Vittix\Panchang\ValueObject\GeoLocation;

final class ViddhaTithiRuleTest extends TestCase
{
    public function testNishitaVyapiniFestival(): void
    {
        $engine = new PurePhpAstronomyEngine();
        $finder = new TransitionFinder($engine);
        
        $tithiCalc = new TithiCalculator($engine);
        $nakshatraCalc = new NakshatraCalculator($engine);
        $yogaCalc = new YogaCalculator($engine);
        $karanaCalc = new KaranaCalculator($engine);
        $varaCalc = new VaraCalculator();
        $lunarMonthCalc = new LunarMonthCalculator($engine);
        $adhikaCalc = new AdhikaMasaCalculator($engine, $lunarMonthCalc);
        
        $panchangaCalc = new PanchangaCalculator(
            $engine,
            $tithiCalc,
            $nakshatraCalc,
            $yogaCalc,
            $karanaCalc,
            $varaCalc,
            $finder,
            $lunarMonthCalc,
            $adhikaCalc
        );

        $festivalEngine = new FestivalEngine($panchangaCalc);
        $muhurtaCalc = new MuhurtaCalculator();

        // Maha Shivaratri: Magha, Krishna Chaturdashi, Nishita vyapini
        $shivaratri = new Festival(
            'Maha Shivaratri',
            new ViddhaTithiRule(LunarMonth::Magha, Tithi::KrishnaChaturdashi, 'Nishita', $muhurtaCalc)
        );
        $festivalEngine->addFestival($shivaratri);

        $location = new GeoLocation(19.0760, 72.8777); // Mumbai
        
        // Maha Shivaratri in 2026 is roughly Feb 15.
        // We will scan a few days around it to find it.
        $foundDates = [];
        for ($i = 10; $i <= 20; $i++) {
            $dateStr = sprintf('2026-02-%02d 00:00:00', $i);
            $date = new DateTimeImmutable($dateStr, new DateTimeZone('Asia/Kolkata'));
            $festivals = $festivalEngine->getFestivalsForDate($date, $location);
            if (in_array('Maha Shivaratri', $festivals, true)) {
                $foundDates[] = $dateStr;
            }
        }
        
        self::assertNotEmpty($foundDates);
        // Specifically, in 2026 it should be Feb 15
        self::assertContains('2026-02-15 00:00:00', $foundDates);
    }
}
