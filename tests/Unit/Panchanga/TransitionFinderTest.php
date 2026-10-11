<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Unit\Panchanga;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Astronomy\Engine\PurePhpAstronomyEngine;
use Vittix\Panchang\Enum\Body;
use Vittix\Panchang\Math\Angle;
use Vittix\Panchang\Panchanga\TransitionFinder;
use Vittix\Panchang\Time\AstroTimeFactory;
use Vittix\Panchang\Time\PolynomialDeltaTProvider;
use Vittix\Panchang\ValueObject\AstroInstant;

/**
 * Convergence tests for TransitionFinder's bracketed refinement.
 *
 * The randomized test compares the finder's result against an independent
 * reference: the first boundary computed by a plain bisection on the
 * *engine's own longitudes* (both sides evaluated at full fractional-JD
 * precision, so the comparison is not limited by whole-second rounding).
 */
final class TransitionFinderTest extends TestCase
{
    private PurePhpAstronomyEngine $engine;
    private TransitionFinder $finder;
    private AstroTimeFactory $timeFactory;

    protected function setUp(): void
    {
        $this->engine = new PurePhpAstronomyEngine();
        $this->finder = new TransitionFinder($this->engine);
        $this->timeFactory = new AstroTimeFactory();
    }

    /**
     * Regression: the karana boundary after 1967-03-13T14:26:41Z used to be
     * returned ≈3.75 h late (the coarse scan bound leaked through after the
     * secant refinement stalled). The corrected refinement must land within
     * one second of the engine's own boundary.
     */
    public function testKaranaRefinementConvergesNearBoundaryRegressionCase(): void
    {
        $start = new DateTimeImmutable('1967-03-13T14:26:41Z');
        $from = $this->timeFactory->fromDateTime($start);
        $until = $this->timeFactory->fromDateTime($start->modify('+5 days'));

        $end = $this->finder->findKaranaEnd($from, $until);
        self::assertNotNull($end, 'Engine found no karana boundary within 5 days.');

        $boundary = $this->engineBoundary('karana', $from->julianDayUt);
        $errorSeconds = abs($end->julianDayUt - $boundary) * 86400.0;

        self::assertLessThanOrEqual(
            1.0,
            $errorSeconds,
            sprintf(
                'Refinement returned %s but the boundary is %s (%.1f s off; the pre-fix code was ~13500 s off).',
                $end->utc->format('Y-m-d H:i:s'),
                $this->jdToUtc($boundary)->format('Y-m-d H:i:s'),
                $errorSeconds,
            ),
        );
    }

    /**
     * The 360°→0° wraparound: the last tithi of a lunation ends exactly at
     * the new moon (elongation wraps around). New moon of 2024-01-11 ≈ 11:57 UTC.
     */
    public function testTithiWraparoundConvergesNearNewMoon(): void
    {
        $start = new DateTimeImmutable('2024-01-11T08:00:00Z');
        $from = $this->timeFactory->fromDateTime($start);
        $until = $this->timeFactory->fromDateTime($start->modify('+2 days'));

        $end = $this->finder->findTithiEnd($from, $until);
        self::assertNotNull($end, 'Engine found no tithi boundary within 2 days.');

        // The new moon (and therefore this boundary) falls on 2024-01-11.
        self::assertSame('2024-01-11', $end->utc->format('Y-m-d'));

        $boundary = $this->engineBoundary('tithi', $from->julianDayUt);
        $errorSeconds = abs($end->julianDayUt - $boundary) * 86400.0;

        self::assertLessThanOrEqual(
            1.0,
            $errorSeconds,
            sprintf('Wraparound refinement is %.1f s from the engine boundary.', $errorSeconds),
        );
    }

    /**
     * Randomized check: for random start instants across 1950–2100 and all
     * four limbs, the returned transition must lie within one second of the
     * first boundary found by an independent bisection on the engine's own
     * longitudes. Seeded for determinism.
     */
    public function testRandomizedTransitionsMatchEngineBoundary(): void
    {
        mt_srand(20260929);

        $epoch = (new DateTimeImmutable('1950-01-01T00:00:00Z'))->getTimestamp();
        $span = (new DateTimeImmutable('2100-12-31T23:00:00Z'))->getTimestamp() - $epoch;
        $limbs = ['tithi', 'nakshatra', 'yoga', 'karana'];

        for ($sample = 0; $sample < 12; $sample++) {
            $timestamp = $epoch + mt_rand(0, $span);
            $start = (new DateTimeImmutable('@' . $timestamp))
                ->setTimezone(new DateTimeZone('UTC'));
            $from = $this->timeFactory->fromDateTime($start);
            $until = $this->timeFactory->fromDateTime($start->modify('+5 days'));

            foreach ($limbs as $limb) {
                $end = match ($limb) {
                    'tithi' => $this->finder->findTithiEnd($from, $until),
                    'nakshatra' => $this->finder->findNakshatraEnd($from, $until),
                    'yoga' => $this->finder->findYogaEnd($from, $until),
                    'karana' => $this->finder->findKaranaEnd($from, $until),
                };
                self::assertNotNull(
                    $end,
                    "No '{$limb}' boundary found within 5 days from {$start->format('Y-m-d H:i:s')}.",
                );

                $boundary = $this->engineBoundary($limb, $from->julianDayUt);
                $errorSeconds = abs($end->julianDayUt - $boundary) * 86400.0;

                self::assertLessThanOrEqual(
                    1.0,
                    $errorSeconds,
                    sprintf(
                        "Limb '%s' from %s: returned %s, boundary %s (%.3f s).",
                        $limb,
                        $start->format('Y-m-d H:i:s'),
                        $end->utc->format('Y-m-d H:i:s'),
                        $this->jdToUtc($boundary)->format('Y-m-d H:i:s'),
                        $errorSeconds,
                    ),
                );
            }
        }
    }

    // -----------------------------------------------------------------------
    // Independent reference (bisection on the engine's own longitudes)
    // -----------------------------------------------------------------------

    private function engineBoundary(string $limb, float $fromJd): float
    {
        $startIndex = $this->limbIndex($limb, $fromJd);

        // Coarse scan in 10-minute steps to bracket the first change.
        $lo = $fromJd;
        $hi = null;
        for ($step = 1; $step <= 800; $step++) {
            $probe = $fromJd + $step / 144.0;
            if ($this->limbIndex($limb, $probe) !== $startIndex) {
                $hi = $probe;
                $lo = $fromJd + ($step - 1) / 144.0;
                break;
            }
        }
        self::assertNotNull($hi, "Reference: no '{$limb}' boundary found from JD {$fromJd}.");

        // Bisect to 0.05 s.
        for ($i = 0; $i < 64 && ($hi - $lo) * 86400.0 > 0.05; $i++) {
            $mid = ($lo + $hi) / 2.0;
            if ($this->limbIndex($limb, $mid) === $startIndex) {
                $lo = $mid;
            } else {
                $hi = $mid;
            }
        }

        return $hi;
    }

    private function limbIndex(string $limb, float $julianDayUt): int
    {
        $instant = $this->instantAtJd($julianDayUt);
        $sun = $this->engine->tropicalLongitude(Body::Sun, $instant);
        $moon = $this->engine->tropicalLongitude(Body::Moon, $instant);

        return match ($limb) {
            'tithi' => (int) floor(Angle::normalize($moon - $sun) / 12.0),
            'karana' => (int) floor(Angle::normalize($moon - $sun) / 6.0),
            'nakshatra' => (int) floor(
                Angle::normalize($this->engine->siderealLongitude(Body::Moon, $instant)) / (360.0 / 27.0)
            ),
            'yoga' => (int) floor(
                Angle::normalize(
                    $this->engine->siderealLongitude(Body::Sun, $instant)
                    + $this->engine->siderealLongitude(Body::Moon, $instant)
                ) / (360.0 / 27.0)
            ),
            default => throw new \InvalidArgumentException("Unknown limb '{$limb}'."),
        };
    }

    /**
     * Fractional-JD AstroInstant (mirrors the production helper so the
     * reference is not limited by whole-second rounding).
     */
    private function instantAtJd(float $julianDayUt): AstroInstant
    {
        $unixSeconds = ($julianDayUt - 2440587.5) * 86400.0;
        $seconds = (int) floor($unixSeconds);
        $microseconds = (int) round(($unixSeconds - $seconds) * 1_000_000);
        if ($microseconds >= 1_000_000) {
            $seconds++;
            $microseconds -= 1_000_000;
        }

        $utc = (new DateTimeImmutable('@' . $seconds))->modify("+{$microseconds} microseconds");
        $decimalYear = (float) $utc->format('Y') + ((int) $utc->format('z')) / 365.25;
        $deltaT = (new PolynomialDeltaTProvider())->getDeltaT($decimalYear);

        return new AstroInstant(
            utc: $utc,
            julianDayUt: $julianDayUt,
            julianDayTt: $julianDayUt + $deltaT / 86400.0,
            deltaTSeconds: $deltaT,
        );
    }

    private function jdToUtc(float $julianDayUt): DateTimeImmutable
    {
        $unixSeconds = ($julianDayUt - 2440587.5) * 86400.0;

        return (new DateTimeImmutable('@' . (int) round($unixSeconds)))->setTimezone(new DateTimeZone('UTC'));
    }
}
