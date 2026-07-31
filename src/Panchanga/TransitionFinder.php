<?php

declare(strict_types=1);

namespace Vittix\Panchang\Panchanga;

use Vittix\Panchang\Astronomy\Contracts\AstronomyEngineInterface;
use Vittix\Panchang\Enum\Body;
use Vittix\Panchang\Math\Angle;
use Vittix\Panchang\Time\AstroTimeFactory;
use Vittix\Panchang\Time\JulianDay;
use Vittix\Panchang\ValueObject\AstroInstant;

/**
 * Numerical root finder for Panchang limb transitions.
 *
 * Strategy:
 *  1. Coarse scan in 30-minute steps to detect an index change.
 *  2. Once a bracket [prev, curr] is found, refine using bisection.
 *
 * The solver is generic: the caller provides a callable that maps an
 * AstroInstant to an integer index (Tithi, Nakshatra, Yoga, or Karana).
 *
 * Why bisection rather than Newton-Raphson?
 *  • Bisection is unconditionally convergent.
 *  • Angular functions are nearly linear over one limb, so bisection
 *    converges in ≈ 15–20 iterations for 1-second accuracy.
 *  • The coarse scan cost dominates; refinement cost is negligible.
 *
 * Brent's method can be added as an optimisation later without changing
 * the calling interface.
 */
final class TransitionFinder
{
    /** Coarse scan step in minutes. */
    private const SCAN_STEP_MINUTES = 30;

    /** Bisection tolerance in seconds. */
    private const TOLERANCE_SECONDS = 5.0;

    /** Maximum bisection iterations. */
    private const MAX_BISECT = 50;

    private readonly AstroTimeFactory $timeFactory;

    public function __construct(
        private readonly AstronomyEngineInterface $astronomy,
    ) {
        $this->timeFactory = new AstroTimeFactory();
    }

    // -------------------------------------------------------------------------
    // Public convenience methods
    // -------------------------------------------------------------------------

    public function findTithiEnd(
        AstroInstant $from,
        AstroInstant $until
    ): ?AstroInstant {
        return $this->find($from, $until, $this->tithiIndexFn());
    }

    public function findNakshatraEnd(
        AstroInstant $from,
        AstroInstant $until
    ): ?AstroInstant {
        return $this->find($from, $until, $this->nakshatraIndexFn());
    }

    public function findYogaEnd(
        AstroInstant $from,
        AstroInstant $until
    ): ?AstroInstant {
        return $this->find($from, $until, $this->yogaIndexFn());
    }

    public function findKaranaEnd(
        AstroInstant $from,
        AstroInstant $until
    ): ?AstroInstant {
        return $this->find($from, $until, $this->karanaIndexFn());
    }

    // -------------------------------------------------------------------------
    // Core solver
    // -------------------------------------------------------------------------

    /**
     * Find the first transition of $indexFn() within [from, until].
     *
     * @param callable(AstroInstant): int $indexFn
     */
    public function find(
        AstroInstant $from,
        AstroInstant $until,
        callable $indexFn
    ): ?AstroInstant {
        if ($until->julianDayUt < $from->julianDayUt) {
            throw new \InvalidArgumentException(
                'Transition search end must not precede its start.'
            );
        }

        $startIndex = $indexFn($from);
        $step       = self::SCAN_STEP_MINUTES / (24.0 * 60.0); // days

        $prevJd = $from->julianDayUt;

        for (
            $jd = $from->julianDayUt + $step;
            $jd <= $until->julianDayUt;
            $jd += $step
        ) {
            $instant = $this->timeFactory->fromJulianDay($jd);

            if ($indexFn($instant) !== $startIndex) {
                // Found a bracket [$prevJd, $jd]
                return $this->bisect($prevJd, $jd, $startIndex, $indexFn);
            }

            $prevJd = $jd;
        }

        // Check if transition happens exactly at the very end
        if ($indexFn($until) !== $startIndex) {
            return $this->bisect($prevJd, $until->julianDayUt, $startIndex, $indexFn);
        }

        return null;
    }

    /**
     * Find all transitions within the given window [from, until].
     *
     * @param callable(AstroInstant): int $indexFn
     * @return AstroInstant[]
     */
    public function findAll(
        AstroInstant $from,
        AstroInstant $until,
        callable $indexFn
    ): array {
        $transitions = [];
        $currentStart = $from;

        while (true) {
            $transition = $this->find($currentStart, $until, $indexFn);
            
            if ($transition === null) {
                break;
            }

            $transitions[] = $transition;
            
            // Advance the search start by 1 minute to clear the boundary
            $nextJd = $transition->julianDayUt + (1.0 / 1440.0);
            $currentStart = $this->timeFactory->fromJulianDay($nextJd);

            if ($currentStart->julianDayUt >= $until->julianDayUt) {
                break;
            }
        }

        return $transitions;
    }

    // -------------------------------------------------------------------------
    // Bisection refiner
    // -------------------------------------------------------------------------

    /**
     * @param callable(AstroInstant): int $indexFn
     */
    private function bisect(
        float $lowerJd,
        float $upperJd,
        int $startIndex,
        callable $indexFn
    ): AstroInstant {
        $tolerance = self::TOLERANCE_SECONDS / 86400.0; // days

        for ($i = 0; $i < self::MAX_BISECT; $i++) {
            $midJd   = ($lowerJd + $upperJd) / 2.0;
            $midInst = $this->timeFactory->fromJulianDay($midJd);

            if ($indexFn($midInst) === $startIndex) {
                $lowerJd = $midJd;
            } else {
                $upperJd = $midJd;
            }

            if (($upperJd - $lowerJd) < $tolerance) {
                break;
            }
        }

        return $this->timeFactory->fromJulianDay($upperJd);
    }

    // -------------------------------------------------------------------------
    // Index callables
    // -------------------------------------------------------------------------

    /** @return callable(AstroInstant): int */
    private function tithiIndexFn(): callable
    {
        $astronomy = $this->astronomy;

        return static function (AstroInstant $instant) use ($astronomy): int {
            $sun  = $astronomy->tropicalLongitude(Body::Sun,  $instant);
            $moon = $astronomy->tropicalLongitude(Body::Moon, $instant);

            return (int) floor(Angle::normalize($moon - $sun) / 12.0);
        };
    }

    /** @return callable(AstroInstant): int */
    private function nakshatraIndexFn(): callable
    {
        $astronomy = $this->astronomy;

        return static function (AstroInstant $instant) use ($astronomy): int {
            $moon = $astronomy->siderealLongitude(Body::Moon, $instant);

            return (int) floor(Angle::normalize($moon) / (360.0 / 27.0));
        };
    }

    /** @return callable(AstroInstant): int */
    private function yogaIndexFn(): callable
    {
        $astronomy = $this->astronomy;

        return static function (AstroInstant $instant) use ($astronomy): int {
            $sun  = $astronomy->siderealLongitude(Body::Sun,  $instant);
            $moon = $astronomy->siderealLongitude(Body::Moon, $instant);

            return (int) floor(Angle::normalize($sun + $moon) / (360.0 / 27.0));
        };
    }

    /** @return callable(AstroInstant): int */
    private function karanaIndexFn(): callable
    {
        $astronomy = $this->astronomy;

        return static function (AstroInstant $instant) use ($astronomy): int {
            $sun  = $astronomy->tropicalLongitude(Body::Sun,  $instant);
            $moon = $astronomy->tropicalLongitude(Body::Moon, $instant);

            return (int) floor(Angle::normalize($moon - $sun) / 6.0);
        };
    }
}
