<?php

declare(strict_types=1);

namespace Vittix\Panchang\Panchanga;

use DateTimeImmutable;
use DateTimeZone;
use Vittix\Panchang\Astronomy\Contracts\AstronomyEngineInterface;
use Vittix\Panchang\Calendar\AdhikaMasaCalculator;
use Vittix\Panchang\Calendar\LunarMonthCalculator;
use Vittix\Panchang\Enum\Body;
use Vittix\Panchang\Enum\Tithi;
use Vittix\Panchang\Time\AstroTimeFactory;
use Vittix\Panchang\Time\SunriseFallback;
use Vittix\Panchang\Enum\MonthSystem;
use Vittix\Panchang\Calendar\PurnimantaMonthCalculator;
use Vittix\Panchang\ValueObject\AccuracyInfo;
use Vittix\Panchang\ValueObject\AstroInstant;
use Vittix\Panchang\ValueObject\GeoLocation;
use Vittix\Panchang\ValueObject\PanchangDay;
use Vittix\Panchang\ValueObject\PanchangMoment;

/**
 * Orchestrates all five Panchanga limbs into PanchangMoment and PanchangDay.
 *
 * PanchangMoment: five limbs at a specific moment in time.
 * PanchangDay:    sunrise-anchored daily Panchang with end times.
 */
final class PanchangaCalculator
{
    private readonly AstroTimeFactory $timeFactory;

    public function __construct(
        private readonly AstronomyEngineInterface $astronomy,
        private readonly TithiCalculator          $tithi,
        private readonly NakshatraCalculator      $nakshatra,
        private readonly YogaCalculator           $yoga,
        private readonly KaranaCalculator         $karana,
        private readonly VaraCalculator           $vara,
        private readonly TransitionFinder         $transitions,
        private readonly ?LunarMonthCalculator    $lunarMonthCalc = null,
        private readonly ?AdhikaMasaCalculator    $adhikaMasaCalc = null,
        private readonly PurnimantaMonthCalculator $purnimantaMonthCalc = new PurnimantaMonthCalculator(),
    ) {
        $this->timeFactory = new AstroTimeFactory();
    }

    /**
     * Calculate all five limbs at a specific moment in time.
     */
    public function forMoment(
        DateTimeImmutable $moment,
        GeoLocation $location
    ): PanchangMoment {
        $instant = $this->timeFactory->fromDateTime($moment);

        $solarEvents  = $this->astronomy->solarEvents(
            $moment,
            $location,
            $moment->getTimezone()
        );

        $sunLon  = $this->astronomy->tropicalLongitude(Body::Sun,  $instant);
        $moonLon = $this->astronomy->tropicalLongitude(Body::Moon, $instant);
        $ayanamsha = $this->astronomy->ayanamsa($instant);

        $tithi    = $this->tithi->calculate($instant);
        $nakshatra = $this->nakshatra->calculate($instant);
        $pada     = $this->nakshatra->pada($instant);
        $yoga     = $this->yoga->calculate($instant);
        $karana   = $this->karana->calculate($instant);
        $vara     = $this->vara->calculate($moment, $solarEvents->sunrise);

        return new PanchangMoment(
            instant: $instant,
            tithi: $tithi,
            nakshatra: $nakshatra,
            pada: $pada,
            yoga: $yoga,
            karana: $karana,
            vara: $vara,
            sunLongitude: $sunLon,
            moonLongitude: $moonLon,
            ayanamsha: $ayanamsha,
            accuracy: AccuracyInfo::standard(),
        );
    }

    /**
     * Calculate a complete sunrise-anchored daily Panchang.
     */
    public function forDay(
        DateTimeImmutable $localDate,
        GeoLocation $location,
        MonthSystem $monthSystem = MonthSystem::Amanta
    ): PanchangDay {
        $timezone = $localDate->getTimezone();

        // Solar events for today
        $solarEvents = $this->astronomy->solarEvents($localDate, $location, $timezone);

        // Use sunrise as the anchor point for the daily Panchang
        $sunriseInstant = null;
        if ($solarEvents->sunrise !== null) {
            $sunriseInstant = $this->timeFactory->fromDateTime($solarEvents->sunrise);
        }

        // Fall back to local noon if no sunrise (shared convention)
        $referenceTime  = SunriseFallback::referenceTime($localDate, $solarEvents->sunrise);
        $referenceInstant = $this->timeFactory->fromDateTime($referenceTime);

        // Five limbs at sunrise (or noon)
        $tithiAtSunrise     = $this->tithi->calculate($referenceInstant);
        $nakshatraAtSunrise = $this->nakshatra->calculate($referenceInstant);
        $padaAtSunrise      = $this->nakshatra->pada($referenceInstant);
        $yogaAtSunrise      = $this->yoga->calculate($referenceInstant);
        $karanaAtSunrise    = $this->karana->calculate($referenceInstant);
        $vara               = $this->vara->calculate($referenceTime, $solarEvents->sunrise);

        // Search for transition ends within the next 48 hours
        $searchUntil = $this->timeFactory->fromDateTime(
            $referenceTime->modify('+48 hours')
        );

        $tithiEnd    = $this->transitions->findTithiEnd($referenceInstant, $searchUntil);
        $nakshatraEnd = $this->transitions->findNakshatraEnd($referenceInstant, $searchUntil);
        $yogaEnd     = $this->transitions->findYogaEnd($referenceInstant, $searchUntil);
        $karanaEnd   = $this->transitions->findKaranaEnd($referenceInstant, $searchUntil);

        // Check for a second Tithi on the same Panchang day
        $tithiSecond     = null;
        $tithiSecondEnd  = null;

        if ($tithiEnd !== null && $solarEvents->sunset !== null) {
            $nextSunrise = $this->astronomy->solarEvents(
                $localDate->modify('+1 day'),
                $location,
                $timezone
            )->sunrise;

            if ($nextSunrise !== null) {
                $nextSunriseInst = $this->timeFactory->fromDateTime($nextSunrise);
                
                if ($tithiEnd->julianDayUt < $nextSunriseInst->julianDayUt) {
                    $tithiSecond = $this->tithi->calculate($tithiEnd);
                    $secondEnd   = $this->transitions->findTithiEnd($tithiEnd, $nextSunriseInst);
                    $tithiSecondEnd = $secondEnd?->utc;
                }
            }
        }

        // Accuracy: check if Tithi end is close to sunrise (within 30 min)
        $nearBoundary = false;
        if ($tithiEnd !== null && $solarEvents->sunrise !== null) {
            $diffSec = abs(
                $tithiEnd->utc->getTimestamp() - $solarEvents->sunrise->getTimestamp()
            );
            $nearBoundary = $diffSec < 1800; // 30 minutes
        }

        $accuracy = AccuracyInfo::standard()->withNearBoundary($nearBoundary);

        // Lunar month, Adhika Masa, and Kshaya Masa (Milestone 8)
        $lunarMonth = null;
        $isAdhika   = false;
        $isKshaya   = false;

        if ($this->lunarMonthCalc !== null) {
            $amantaMonth = $this->lunarMonthCalc->amantaMonth($referenceInstant);
            $isKshaya    = $this->lunarMonthCalc->isKshayaMasa($referenceInstant);
            
            if ($monthSystem === MonthSystem::Purnimanta) {
                $lunarMonth = $this->purnimantaMonthCalc->purnimantaMonth($amantaMonth, $tithiAtSunrise->paksha());
            } else {
                $lunarMonth = $amantaMonth;
            }
        }

        if ($this->adhikaMasaCalc !== null) {
            $isAdhika = $this->adhikaMasaCalc->isAdhikaMasa($referenceInstant);
        }

        return new PanchangDay(
            date: $localDate,
            timezone: $timezone,
            location: $location,
            solarEvents: $solarEvents,
            tithiAtSunrise: $tithiAtSunrise,
            nakshatraAtSunrise: $nakshatraAtSunrise,
            padaAtSunrise: $padaAtSunrise,
            yogaAtSunrise: $yogaAtSunrise,
            karanaAtSunrise: $karanaAtSunrise,
            vara: $vara,
            tithiEndsAt: $tithiEnd?->utc->setTimezone($timezone),
            nakshatraEndsAt: $nakshatraEnd?->utc->setTimezone($timezone),
            yogaEndsAt: $yogaEnd?->utc->setTimezone($timezone),
            karanaEndsAt: $karanaEnd?->utc->setTimezone($timezone),
            tithiSecond: $tithiSecond,
            tithiSecondEndsAt: $tithiSecondEnd?->setTimezone($timezone),
            lunarMonth: $lunarMonth,
            paksha: $tithiAtSunrise->paksha(),
            isAdhikaMasa: $isAdhika,
            isKshayaMasa: $isKshaya,
            accuracy: $accuracy,
        );
    }
}
