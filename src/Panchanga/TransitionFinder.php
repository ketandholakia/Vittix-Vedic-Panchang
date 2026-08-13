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
        return $this->find($from, $until, $this->tithiIndexFn(), $this->tithiFractionFn());
    }

    public function findNakshatraEnd(
        AstroInstant $from,
        AstroInstant $until
    ): ?AstroInstant {
        return $this->find($from, $until, $this->nakshatraIndexFn(), $this->nakshatraFractionFn());
    }

    public function findYogaEnd(
        AstroInstant $from,
        AstroInstant $until
    ): ?AstroInstant {
        return $this->find($from, $until, $this->yogaIndexFn(), $this->yogaFractionFn());
    }

    public function findKaranaEnd(
        AstroInstant $from,
        AstroInstant $until
    ): ?AstroInstant {
        return $this->find($from, $until, $this->karanaIndexFn(), $this->karanaFractionFn());
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
        callable $indexFn,
        ?callable $fractionFn = null
    ): ?AstroInstant {
        if ($until->julianDayUt < $from->julianDayUt) {
            throw new \InvalidArgumentException(
                'Transition search end must not precede its start.'
            );
        }

        $startIndex = $indexFn($from);
        
        $prevJd = $from->julianDayUt;
        $jd = $prevJd;

        while ($jd <= $until->julianDayUt) {
            $instant = $this->timeFactory->fromJulianDay($jd);

            if ($indexFn($instant) !== $startIndex) {
                // Found a bracket [$prevJd, $jd]
                return $this->bisect($prevJd, $jd, $startIndex, $indexFn, $fractionFn);
            }

            $prevJd = $jd;

            // Adaptive step size based on remaining fraction of the limb
            $step = self::SCAN_STEP_MINUTES / 1440.0;
            if ($fractionFn !== null) {
                $fractionRemaining = $fractionFn($instant);
                // Safe minimum speed estimate (e.g. slowest Tithi is ~1.06 days, so * 0.8 is safe)
                // If we are 0.5 through the limb, we can safely jump 0.5 * 0.8 = 0.4 days.
                // We cap the minimum jump at SCAN_STEP_MINUTES to ensure progress.
                $adaptiveStep = $fractionRemaining * 0.8; 
                if ($adaptiveStep > $step) {
                    $step = $adaptiveStep;
                }
            }

            $jd += $step;
        }

        // Check if transition happens exactly at the very end
        if ($indexFn($until) !== $startIndex) {
            return $this->bisect($prevJd, $until->julianDayUt, $startIndex, $indexFn, $fractionFn);
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
        callable $indexFn,
        ?callable $fractionFn = null
    ): array {
        $transitions = [];
        $currentStart = $from;

        while (true) {
            $transition = $this->find($currentStart, $until, $indexFn, $fractionFn);
            
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
        callable $indexFn,
        ?callable $fractionFn = null
    ): AstroInstant {
        $tolerance = self::TOLERANCE_SECONDS / 86400.0; // days
        
        // If we don't have a fraction function to interpolate with, fallback to pure bisection
        if ($fractionFn === null) {
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

        // Secant method (Linear interpolation)
        // fractionFn goes from ~1.0 down to 0.0 as it approaches the boundary.
        // We want to find the root where fractionFn is 0.
        
        $x0 = $lowerJd;
        $x1 = $upperJd;
        
        $f0 = $fractionFn($this->timeFactory->fromJulianDay($x0));
        // If the bracket crossed the boundary, f1 will be close to 1.0 (wrapped around)
        // We want to treat it as a negative fraction remaining.
        $f1 = $fractionFn($this->timeFactory->fromJulianDay($x1));
        if ($f1 > 0.5) {
            $f1 = $f1 - 1.0; 
        }

        for ($i = 0; $i < self::MAX_BISECT; $i++) {
            if (abs($f1 - $f0) < 1e-9) {
                // Prevent division by zero
                $x2 = ($x0 + $x1) / 2.0;
            } else {
                // Secant formula
                $x2 = $x1 - $f1 * ($x1 - $x0) / ($f1 - $f0);
            }
            
            // Ensure x2 stays within reasonable bounds, otherwise fallback to bisection
            if ($x2 < $lowerJd || $x2 > $upperJd) {
                $x2 = ($lowerJd + $upperJd) / 2.0;
            }

            $inst = $this->timeFactory->fromJulianDay($x2);
            $idx = $indexFn($inst);
            
            if ($idx === $startIndex) {
                $lowerJd = $x2;
                $x0 = $x2;
                $f0 = $fractionFn($inst);
            } else {
                $upperJd = $x2;
                $x1 = $x2;
                $f1 = $fractionFn($inst);
                if ($f1 > 0.5) {
                    $f1 = $f1 - 1.0; 
                }
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

    /** @return callable(AstroInstant): float */
    private function tithiFractionFn(): callable
    {
        $astronomy = $this->astronomy;
        return static function (AstroInstant $instant) use ($astronomy): float {
            $sun  = $astronomy->tropicalLongitude(Body::Sun,  $instant);
            $moon = $astronomy->tropicalLongitude(Body::Moon, $instant);
            $angle = Angle::normalize($moon - $sun);
            return 1.0 - fmod($angle, 12.0) / 12.0;
        };
    }

    /** @return callable(AstroInstant): float */
    private function nakshatraFractionFn(): callable
    {
        $astronomy = $this->astronomy;
        return static function (AstroInstant $instant) use ($astronomy): float {
            $moon = $astronomy->siderealLongitude(Body::Moon, $instant);
            $angle = Angle::normalize($moon);
            return 1.0 - fmod($angle, 360.0 / 27.0) / (360.0 / 27.0);
        };
    }

    /** @return callable(AstroInstant): float */
    private function yogaFractionFn(): callable
    {
        $astronomy = $this->astronomy;
        return static function (AstroInstant $instant) use ($astronomy): float {
            $sun  = $astronomy->siderealLongitude(Body::Sun,  $instant);
            $moon = $astronomy->siderealLongitude(Body::Moon, $instant);
            $angle = Angle::normalize($sun + $moon);
            return 1.0 - fmod($angle, 360.0 / 27.0) / (360.0 / 27.0);
        };
    }

    /** @return callable(AstroInstant): float */
    private function karanaFractionFn(): callable
    {
        $astronomy = $this->astronomy;
        return static function (AstroInstant $instant) use ($astronomy): float {
            $sun  = $astronomy->tropicalLongitude(Body::Sun,  $instant);
            $moon = $astronomy->tropicalLongitude(Body::Moon, $instant);
            $angle = Angle::normalize($moon - $sun);
            return 1.0 - fmod($angle, 6.0) / 6.0;
        };
    }
}
