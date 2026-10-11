<?php

declare(strict_types=1);

namespace Vittix\Panchang\Panchanga;

use DateTimeImmutable;
use DateTimeZone;
use Vittix\Panchang\Astronomy\Contracts\AstronomyEngineInterface;
use Vittix\Panchang\Enum\Body;
use Vittix\Panchang\Math\Angle;
use Vittix\Panchang\Time\DeltaTProviderInterface;
use Vittix\Panchang\Time\PolynomialDeltaTProvider;
use Vittix\Panchang\ValueObject\AstroInstant;

/**
 * Numerical root finder for Panchang limb transitions.
 *
 * Strategy:
 *  1. Coarse scan in ≤ 0.2-day steps to bracket the first index change.
 *     The scan step is capped at 0.2 days so that a bracket can never
 *     contain more than one boundary (the shortest limb — karana — lasts
 *     ≈ 0.42 days even at the Moon's maximum speed).
 *  2. Refinement with a bracketed root finder on the limb fraction:
 *       • the signed distance g = fractionRemaining (inside the limb) or
 *         fractionRemaining − 1 (past the boundary) is smooth and monotone
 *         through the crossing, including the 360°→0° wraparound;
 *       • a secant (regula falsi) step proposes the next iterate;
 *       • if a step does not shrink the bracket by at least half, an
 *         unconditional bisection step is taken;
 *       • evaluation happens at full (fractional) Julian Day precision —
 *         instants are never rounded to whole seconds;
 *       • convergence target is 0.5 s; if the loop fails to converge a
 *         TransitionSearchException is thrown instead of silently returning
 *         an unrefined bound.
 *
 * The solver is generic: the caller provides a callable that maps an
 * AstroInstant to an integer index (Tithi, Nakshatra, Yoga, or Karana).
 */
final class TransitionFinder
{
    /** Minimum coarse scan step in minutes (safety floor for progress). */
    private const SCAN_STEP_MINUTES = 30;

    /**
     * Maximum coarse scan step in days. Must stay well below the duration of
     * the shortest limb so a bracket never contains two boundaries.
     */
    private const MAX_SCAN_STEP_DAYS = 0.2;

    /** Refinement convergence tolerance in seconds. */
    private const TOLERANCE_SECONDS = 0.5;

    /** Maximum refinement iterations before failing loudly. */
    private const MAX_ITERATIONS = 100;

    /** Seconds between the JD and Unix epochs. */
    private const UNIX_EPOCH_JD = 2440587.5;

    private readonly DeltaTProviderInterface $deltaTProvider;

    public function __construct(
        private readonly AstronomyEngineInterface $astronomy,
    ) {
        $this->deltaTProvider = new PolynomialDeltaTProvider();
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
     * @param (callable(AstroInstant): float)|null $fractionFn
     * @throws TransitionSearchException when refinement cannot converge.
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
            $instant = $this->instantAtJd($jd);

            if ($indexFn($instant) !== $startIndex) {
                // Found a bracket [$prevJd, $jd]
                return $this->refine($prevJd, $jd, $startIndex, $indexFn, $fractionFn);
            }

            $prevJd = $jd;

            // Adaptive step size based on the remaining fraction of the limb,
            // capped so that a single step cannot skip a whole limb boundary.
            $step = self::SCAN_STEP_MINUTES / 1440.0;
            if ($fractionFn !== null) {
                $fractionRemaining = $fractionFn($instant);
                $adaptiveStep = $fractionRemaining * 0.8;
                if ($adaptiveStep > $step) {
                    $step = $adaptiveStep;
                }
            }
            if (!is_finite($step) || $step <= 0.0) {
                $step = self::SCAN_STEP_MINUTES / 1440.0;
            }
            if ($step > self::MAX_SCAN_STEP_DAYS) {
                $step = self::MAX_SCAN_STEP_DAYS;
            }

            $jd += $step;
        }

        // Check if the transition happens exactly at the very end of the window.
        if ($indexFn($until) !== $startIndex) {
            return $this->refine($prevJd, $until->julianDayUt, $startIndex, $indexFn, $fractionFn);
        }

        return null;
    }

    /**
     * Find all transitions within the given window [from, until].
     *
     * @param callable(AstroInstant): int $indexFn
     * @param (callable(AstroInstant): float)|null $fractionFn
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

            // Advance the search start by 1 minute to clear the boundary.
            $nextJd = $transition->julianDayUt + (1.0 / 1440.0);
            $currentStart = $this->instantAtJd($nextJd);

            if ($currentStart->julianDayUt >= $until->julianDayUt) {
                break;
            }
        }

        return $transitions;
    }

    // -------------------------------------------------------------------------
    // Bracketed refinement
    // -------------------------------------------------------------------------

    /**
     * Refine the first boundary between $lowerJd (index == startIndex) and
     * $upperJd (index != startIndex).
     *
     * @param callable(AstroInstant): int $indexFn
     * @param (callable(AstroInstant): float)|null $fractionFn
     * @throws TransitionSearchException when the bracket is invalid or convergence fails.
     */
    private function refine(
        float $lowerJd,
        float $upperJd,
        int $startIndex,
        callable $indexFn,
        ?callable $fractionFn
    ): AstroInstant {
        $tolerance = self::TOLERANCE_SECONDS / 86400.0;

        if ($fractionFn === null) {
            // Pure bisection: no auxiliary function to interpolate with.
            for ($i = 0; $i < self::MAX_ITERATIONS; $i++) {
                if (($upperJd - $lowerJd) <= $tolerance) {
                    return $this->instantAtJd($upperJd);
                }
                $midJd = ($lowerJd + $upperJd) / 2.0;
                if ($indexFn($this->instantAtJd($midJd)) === $startIndex) {
                    $lowerJd = $midJd;
                } else {
                    $upperJd = $midJd;
                }
            }

            throw new TransitionSearchException(sprintf(
                'Transition refinement (bisection) failed to converge to %.1f s after %d iterations.',
                self::TOLERANCE_SECONDS,
                self::MAX_ITERATIONS
            ));
        }

        $lo = $lowerJd;
        $hi = $upperJd;
        $gLo = $this->signedFraction($fractionFn, $indexFn, $startIndex, $lo);
        $gHi = $this->signedFraction($fractionFn, $indexFn, $startIndex, $hi);

        if ($gLo <= 0.0 || $gHi > 0.0) {
            throw new TransitionSearchException(sprintf(
                'Invalid transition bracket: expected signed fraction > 0 at the lower bound and <= 0 at the upper bound, got %.6F and %.6F.',
                $gLo,
                $gHi
            ));
        }

        for ($i = 0; $i < self::MAX_ITERATIONS; $i++) {
            if (($hi - $lo) <= $tolerance) {
                return $this->instantAtJd($hi);
            }
            if ($gHi === 0.0) {
                // The upper bound sits exactly on the boundary.
                return $this->instantAtJd($hi);
            }

            $width = $hi - $lo;

            // Secant (regula falsi) step on the signed limb fraction.
            $x = $hi - $gHi * ($hi - $lo) / ($gHi - $gLo);
            if (!($x > $lo + 1e-12 && $x < $hi - 1e-12)) {
                $x = $lo + $width / 2.0;
            }

            $gx = $this->signedFraction($fractionFn, $indexFn, $startIndex, $x);
            if ($gx > 0.0) {
                $lo = $x;
                $gLo = $gx;
            } else {
                $hi = $x;
                $gHi = $gx;
            }

            // Safeguard: if the step did not shrink the bracket by at least
            // half, take an unconditional bisection step.
            if (($hi - $lo) > 0.5 * $width) {
                $x = ($lo + $hi) / 2.0;
                $gx = $this->signedFraction($fractionFn, $indexFn, $startIndex, $x);
                if ($gx > 0.0) {
                    $lo = $x;
                    $gLo = $gx;
                } else {
                    $hi = $x;
                    $gHi = $gx;
                }
            }
        }

        throw new TransitionSearchException(sprintf(
            'Transition refinement failed to converge to %.1f s after %d iterations (bracket width %.3F s).',
            self::TOLERANCE_SECONDS,
            self::MAX_ITERATIONS,
            ($hi - $lo) * 86400.0
        ));
    }

    /**
     * Signed distance to the limb boundary, in limb-fraction units:
     *   +fractionRemaining  before the boundary (g → 0+ at the crossing)
     *   fractionRemaining−1 after it          (g → 0− at the crossing)
     * The function is continuous and monotone through the crossing,
     * including the 360°→0° wraparound, because the underlying longitude is
     * monotone (mod 360°) across a single boundary.
     *
     * @param callable(AstroInstant): int $indexFn
     * @param callable(AstroInstant): float $fractionFn
     */
    private function signedFraction(
        callable $fractionFn,
        callable $indexFn,
        int $startIndex,
        float $jd
    ): float {
        $instant = $this->instantAtJd($jd);
        $remaining = $fractionFn($instant);

        if ($indexFn($instant) !== $startIndex) {
            return $remaining - 1.0;
        }

        return $remaining;
    }

    // -------------------------------------------------------------------------
    // Fractional-precision instants
    // -------------------------------------------------------------------------

    /**
     * Build an AstroInstant at full (fractional) Julian Day precision.
     *
     * Unlike AstroTimeFactory::fromJulianDay(), the instant is NOT rounded to
     * whole seconds: the root finder needs sub-second resolution to converge
     * to its 0.5 s tolerance. Position calculations only depend on
     * julianDayTt, so the microsecond-precision UTC presentation is exact
     * enough for display purposes.
     */
    private function instantAtJd(float $julianDayUt): AstroInstant
    {
        $unixSeconds = ($julianDayUt - self::UNIX_EPOCH_JD) * 86400.0;
        $seconds = (int) floor($unixSeconds);
        $microseconds = (int) round(($unixSeconds - $seconds) * 1_000_000);
        if ($microseconds >= 1_000_000) {
            $seconds++;
            $microseconds -= 1_000_000;
        }

        $utc = (new DateTimeImmutable('@' . $seconds))->modify("+{$microseconds} microseconds");

        $decimalYear = (float) $utc->format('Y') + ((int) $utc->format('z')) / 365.25;
        $deltaT = $this->deltaTProvider->getDeltaT($decimalYear);

        return new AstroInstant(
            utc: $utc,
            julianDayUt: $julianDayUt,
            julianDayTt: $julianDayUt + $deltaT / 86400.0,
            deltaTSeconds: $deltaT,
        );
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
