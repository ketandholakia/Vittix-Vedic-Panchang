<?php

declare(strict_types=1);

namespace Vittix\Panchang;

use DateTimeImmutable;
use DateTimeZone;
use Vittix\Panchang\Astronomy\Engine\PurePhpAstronomyEngine;
use Vittix\Panchang\Astronomy\Contracts\AstronomyEngineInterface;
use Vittix\Panchang\Calendar\AdhikaMasaCalculator;
use Vittix\Panchang\Calendar\LunarMonthCalculator;
use Vittix\Panchang\Panchanga\JanmarashiCalculator;
use Vittix\Panchang\Panchanga\KaranaCalculator;
use Vittix\Panchang\Panchanga\NakshatraCalculator;
use Vittix\Panchang\Panchanga\PanchangaCalculator;
use Vittix\Panchang\Panchanga\TithiCalculator;
use Vittix\Panchang\Panchanga\TransitionFinder;
use Vittix\Panchang\Panchanga\VaraCalculator;
use Vittix\Panchang\Panchanga\YogaCalculator;
use Vittix\Panchang\Time\AstroTimeFactory;
use Vittix\Panchang\ValueObject\GeoLocation;
use Vittix\Panchang\ValueObject\PanchangDay;
use Vittix\Panchang\ValueObject\PanchangMoment;

/**
 * Public facade for the Panchang engine.
 *
 * Usage:
 *
 *   $panchang = Panchang::createDefault();
 *
 *   $day = $panchang->day(
 *       new DateTimeImmutable('2026-07-31', new DateTimeZone('Asia/Kolkata')),
 *       new GeoLocation(latitude: 23.0225, longitude: 72.5714)
 *   );
 *
 *   echo $day->tithiAtSunrise->nameEnglish();
 *   echo $day->solarEvents->sunrise?->format('H:i:s');
 *
 * This class does not extend anything and has no abstract methods.
 * It is a simple composition root for the entire engine.
 */
final class Panchang
{
    private readonly PanchangaCalculator $calculator;
    private readonly JanmarashiCalculator $janmarashi;

    private function __construct(
        PanchangaCalculator $calculator,
        JanmarashiCalculator $janmarashi,
    )
    {
        $this->calculator = $calculator;
        $this->janmarashi = $janmarashi;
    }

    /**
     * Create a Panchang instance using the default pure-PHP Lahiri engine.
     *
     * No arguments required. Works on any PHP 8.2+ installation without
     * extensions, native libraries, or network access.
     */
    public static function createDefault(): self
    {
        $engine = new PurePhpAstronomyEngine();

        return self::create($engine);
    }

    /**
     * Create a Panchang instance with a custom astronomy engine.
     *
     * Useful for:
     *  • Injecting a higher-precision engine (VSOP87 + ELP2000 full series).
     *  • Injecting a mock engine in tests.
     *  • Injecting a wrapped native engine if available.
     */
    public static function create(AstronomyEngineInterface $engine): self
    {
        $lunarMonthCalc = new LunarMonthCalculator($engine);
        $adhikaMasaCalc = new AdhikaMasaCalculator($engine, $lunarMonthCalc);

        $calculator = new PanchangaCalculator(
            astronomy:       $engine,
            tithi:           new TithiCalculator($engine),
            nakshatra:       new NakshatraCalculator($engine),
            yoga:            new YogaCalculator($engine),
            karana:          new KaranaCalculator($engine),
            vara:            new VaraCalculator(),
            transitions:     new TransitionFinder($engine),
            lunarMonthCalc:  $lunarMonthCalc,
            adhikaMasaCalc:  $adhikaMasaCalc,
        );

        return new self($calculator, new JanmarashiCalculator($engine));
    }

    /**
     * Calculate the sunrise-anchored daily Panchang for a date and location.
     */
    public function day(
        DateTimeImmutable $date,
        GeoLocation $location
    ): PanchangDay {
        return $this->calculator->forDay($date, $location);
    }

    /**
     * Calculate all five Panchang limbs for a specific moment and location.
     */
    public function moment(
        DateTimeImmutable $moment,
        GeoLocation $location
    ): PanchangMoment {
        return $this->calculator->forMoment($moment, $location);
    }

    /**
     * Calculate the Janmarashi (Moon sign) at a specific moment.
     */
    public function janmarashi(DateTimeImmutable $moment): \Vittix\Panchang\Enum\Rashi
    {
        $instant = (new AstroTimeFactory())->fromDateTime($moment);

        return $this->janmarashi->calculate($instant);
    }
}
