<?php

declare(strict_types=1);

namespace Vittix\Panchang;

use DateTimeImmutable;
use Vittix\Panchang\Astronomy\Engine\PurePhpAstronomyEngine;
use Vittix\Panchang\Enum\Body;
use Vittix\Panchang\Astronomy\Contracts\AstronomyEngineInterface;
use Vittix\Panchang\Astronomy\Coordinates\AscendantCalculator;
use Vittix\Panchang\Astronomy\Coordinates\NutationCalculator;
use Vittix\Panchang\Astronomy\Coordinates\ObliquityCalculator;
use Vittix\Panchang\Astronomy\Sidereal\SiderealTimeCalculator;
use Vittix\Panchang\ValueObject\AscendantPosition;
use Vittix\Panchang\Calendar\AdhikaMasaCalculator;
use Vittix\Panchang\Calendar\LunarMonthCalculator;
use Vittix\Panchang\Astrology\KundaliCalculator;
use Vittix\Panchang\Astrology\DignityCalculator;
use Vittix\Panchang\Astrology\AspectCalculator;
use Vittix\Panchang\Astrology\VimshottariDashaCalculator;
use Vittix\Panchang\Astrology\VargaCalculator;
use Vittix\Panchang\Astrology\ShadbalaCalculator;
use Vittix\Panchang\Astrology\Shadbala\AyanaBalaCalculator;
use Vittix\Panchang\Astrology\Shadbala\ChestaBalaCalculator;
use Vittix\Panchang\Astrology\Shadbala\SthanaBalaCalculator;
use Vittix\Panchang\Astrology\Shadbala\DikBalaCalculator;
use Vittix\Panchang\Astrology\Shadbala\NaisargikaBalaCalculator;
use Vittix\Panchang\Astrology\YogaDetector;
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
use Vittix\Panchang\ValueObject\Kundali;
use Vittix\Panchang\ValueObject\DashaPeriod;
use Vittix\Panchang\ValueObject\Shadbala\Shadbala;
use Vittix\Panchang\Enum\Rashi;
use Vittix\Panchang\Enum\Varga;
use Vittix\Panchang\Festivals\FestivalEngine;
use Vittix\Panchang\Festivals\FestivalCatalog;
use Vittix\Panchang\Muhurta\MuhurtaCalculator;
use Vittix\Panchang\Electional\ElectionalEvaluator;
use Vittix\Panchang\Calendar\HinduCalendarCalculator;
use Vittix\Panchang\Calendar\SamvatCalculator;
use Vittix\Panchang\Calendar\HinduCalendarResult;

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
    private readonly HinduCalendarCalculator $calendar;
    private readonly AscendantCalculator $ascendant;
    private readonly KundaliCalculator $kundali;
    private readonly VimshottariDashaCalculator $dasha;
    private readonly VargaCalculator $varga;
    private readonly ShadbalaCalculator $shadbala;
    private readonly YogaDetector $yogaDetector;
    private readonly AstronomyEngineInterface $astronomy;

    private function __construct(
        PanchangaCalculator $calculator,
        JanmarashiCalculator $janmarashi,
        HinduCalendarCalculator $calendar,
        AscendantCalculator $ascendant,
        KundaliCalculator $kundali,
        VimshottariDashaCalculator $dasha,
        VargaCalculator $varga,
        ShadbalaCalculator $shadbala,
        YogaDetector $yogaDetector,
        AstronomyEngineInterface $astronomy,
    )
    {
        $this->calculator = $calculator;
        $this->janmarashi = $janmarashi;
        $this->calendar = $calendar;
        $this->ascendant = $ascendant;
        $this->kundali = $kundali;
        $this->dasha = $dasha;
        $this->varga = $varga;
        $this->shadbala = $shadbala;
        $this->yogaDetector = $yogaDetector;
        $this->astronomy = $astronomy;
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

        $calendar = new HinduCalendarCalculator(
            $lunarMonthCalc,
            $adhikaMasaCalc,
            new SamvatCalculator()
        );

        $nutation  = new NutationCalculator();
        $ascendant = new AscendantCalculator(
            new SiderealTimeCalculator(),
            new ObliquityCalculator($nutation),
            $engine,
        );

        $kundali = new KundaliCalculator($engine, $ascendant, new DignityCalculator(), new AspectCalculator());
        $dasha = new VimshottariDashaCalculator();
        $varga = new VargaCalculator(new DignityCalculator(), new AspectCalculator());
        $shadbala = new ShadbalaCalculator(
            new SthanaBalaCalculator(),
            new DikBalaCalculator(),
            new NaisargikaBalaCalculator(),
            new AyanaBalaCalculator(),
            new ChestaBalaCalculator()
        );
        $yogas = new YogaDetector();

        return new self($calculator, new JanmarashiCalculator($engine), $calendar, $ascendant, $kundali, $dasha, $varga, $shadbala, $yogas, $engine);
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
    public function janmarashi(DateTimeImmutable $moment): Rashi
    {
        $instant = (new AstroTimeFactory())->fromDateTime($moment);

        return $this->janmarashi->calculate($instant);
    }

    /**
     * Get the underlying PanchangaCalculator.
     */
    public function calculator(): PanchangaCalculator
    {
        return $this->calculator;
    }

    /**
     * Get the FestivalEngine pre-configured with default festivals.
     */
    public function festivals(): FestivalEngine
    {
        $muhurta = new MuhurtaCalculator();
        $engine = new FestivalEngine($this->calculator);

        foreach (FestivalCatalog::getDefaultFestivals($muhurta) as $festival) {
            $engine->addFestival($festival);
        }

        return $engine;
    }

    /**
     * Get a MuhurtaCalculator instance.
     */
    public function muhurta(): MuhurtaCalculator
    {
        return new MuhurtaCalculator();
    }

    /**
     * Get an ElectionalEvaluator instance.
     */
    public function electional(): ElectionalEvaluator
    {
        return new ElectionalEvaluator();
    }

    /**
     * Get the Hindu Calendar information (Samvat, Adhika Masa, etc) for a specific date.
     */
    public function calendar(DateTimeImmutable $date): HinduCalendarResult
    {
        $instant = (new AstroTimeFactory())->fromDateTime($date);

        return $this->calendar->calculate($instant, $date->getTimezone());
    }

    /**
     * Calculate the Ascendant (Lagna) for a specific moment and location.
     *
     * This is the foundational reference point for Kundali / natal chart
     * house assignment (Bhava): once the Ascendant's Rashi is known, each
     * planet's house is its Rashi position counted from the Ascendant's
     * Rashi (whole-sign houses).
     */
    public function ascendant(DateTimeImmutable $moment, GeoLocation $location): AscendantPosition
    {
        $instant = (new AstroTimeFactory())->fromDateTime($moment);

        return $this->ascendant->calculate($instant, $location);
    }

    /**
     * Calculate the full Kundali (Birth Chart) for a specific moment and location.
     * Includes the Ascendant and all planetary placements in their respective Rashis and Houses.
     */
    public function kundali(DateTimeImmutable $moment, GeoLocation $location): Kundali
    {
        $instant = (new AstroTimeFactory())->fromDateTime($moment);

        return $this->kundali->calculate($instant, $location);
    }

    /**
     * Calculates the Vimshottari Dasha (planetary time periods) starting from a specific birth moment.
     *
     * @return array<DashaPeriod>
     */
    public function vimshottariDasha(DateTimeImmutable $birthTime): array
    {
        $instant = (new AstroTimeFactory())->fromDateTime($birthTime);
        $moonLongitude = $this->astronomy->siderealLongitude(Body::Moon, $instant);

        return $this->dasha->calculate($birthTime, $moonLongitude);
    }

    /**
     * Calculate a specific Divisional Chart (Varga) for a given moment and location.
     */
    public function varga(DateTimeImmutable $moment, GeoLocation $location, Varga $varga): Kundali
    {
        $baseKundali = $this->kundali($moment, $location);

        return $this->varga->calculate($baseKundali, $varga);
    }

    /**
     * Calculates the Shadbala (Six-fold planetary strength) for the visible planets.
     */
    public function shadbala(DateTimeImmutable $moment, GeoLocation $location): Shadbala
    {
        $d1Kundali = $this->kundali($moment, $location);

        return $this->shadbala->calculate($d1Kundali);
    }

    /**
     * Detects all classical planetary Yogas present in the birth chart.
     *
     * @return array<int, \Vittix\Panchang\ValueObject\Yoga>
     */
    public function yogas(DateTimeImmutable $moment, GeoLocation $location): array
    {
        $d1Kundali = $this->kundali($moment, $location);

        return $this->yogaDetector->detect($d1Kundali);
    }
}
