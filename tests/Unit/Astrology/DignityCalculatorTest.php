<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Unit\Astrology;

use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Astrology\DignityCalculator;
use Vittix\Panchang\Enum\Body;
use Vittix\Panchang\Enum\Dignity;
use Vittix\Panchang\Enum\Rashi;

final class DignityCalculatorTest extends TestCase
{
    private DignityCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new DignityCalculator();
    }

    public function testSunDignities(): void
    {
        // Exalted in Aries (Mesha)
        $this->assertSame(
            Dignity::Exalted,
            $this->calculator->calculate(Body::Sun, Rashi::Mesha, 10.0)
        );

        // Debilitated in Libra (Tula)
        $this->assertSame(
            Dignity::Debilitated,
            $this->calculator->calculate(Body::Sun, Rashi::Tula, 10.0)
        );

        // Moolatrikona in Leo (Simha) up to 20 deg
        $this->assertSame(
            Dignity::Moolatrikona,
            $this->calculator->calculate(Body::Sun, Rashi::Simha, 15.0)
        );

        // Own sign in Leo (Simha) after 20 deg
        $this->assertSame(
            Dignity::OwnSign,
            $this->calculator->calculate(Body::Sun, Rashi::Simha, 25.0)
        );

        // Null for neutral/other signs (for now)
        $this->assertNull(
            $this->calculator->calculate(Body::Sun, Rashi::Mithuna, 15.0)
        );
    }

    public function testJupiterDignities(): void
    {
        // Exalted in Cancer
        $this->assertSame(
            Dignity::Exalted,
            $this->calculator->calculate(Body::Jupiter, Rashi::Karka, 5.0)
        );

        // Debilitated in Capricorn
        $this->assertSame(
            Dignity::Debilitated,
            $this->calculator->calculate(Body::Jupiter, Rashi::Makara, 5.0)
        );

        // Moolatrikona in Sagittarius <= 10
        $this->assertSame(
            Dignity::Moolatrikona,
            $this->calculator->calculate(Body::Jupiter, Rashi::Dhanu, 5.0)
        );
        
        // Own sign in Sagittarius > 10
        $this->assertSame(
            Dignity::OwnSign,
            $this->calculator->calculate(Body::Jupiter, Rashi::Dhanu, 15.0)
        );

        // Own sign in Pisces
        $this->assertSame(
            Dignity::OwnSign,
            $this->calculator->calculate(Body::Jupiter, Rashi::Meena, 10.0)
        );
    }

    public function testDegreeRefinementDisabledCollapsesToCoarseClassification(): void
    {
        // With refinement off, Simha always reports OwnSign for the Sun
        // regardless of degree — the Moolatrikona/OwnSign split is only
        // meaningful when $longitude actually places the Sun in Simha.
        $this->assertSame(
            Dignity::OwnSign,
            $this->calculator->calculate(Body::Sun, Rashi::Simha, 5.0, applyDegreeRefinement: false)
        );
        $this->assertSame(
            Dignity::OwnSign,
            $this->calculator->calculate(Body::Sun, Rashi::Simha, 29.9, applyDegreeRefinement: false)
        );

        // Exalted/Debilitated signs are unaffected by the flag (no degree
        // sub-classification exists for them in the first place).
        $this->assertSame(
            Dignity::Exalted,
            $this->calculator->calculate(Body::Sun, Rashi::Mesha, 5.0, applyDegreeRefinement: false)
        );
    }

    public function testVargaDignityDoesNotMisuseUnrelatedD1Degree(): void
    {
        // Regression test for the Varga dignity bug: previously, a planet's
        // raw D1 longitude (e.g. 25° within whatever D1 sign it actually
        // occupies) was fed directly as the degree-within-sign for a
        // *different*, varga-mapped Rashi. Here Mars is passed a D1
        // longitude of 205° (i.e. 25° into whatever D1 sign that
        // corresponds to — irrelevant), against a varga-mapped Rashi of
        // Mesha. Without the fix, 25° > 12° would (coincidentally and
        // wrongly) classify this as OwnSign instead of Moolatrikona, or
        // vice versa depending on the coincidental degree — the point is
        // it must not depend on the unrelated raw degree at all once
        // refinement is disabled, and must always resolve to the same
        // coarse OwnSign regardless of what that raw degree happens to be.
        $resultA = $this->calculator->calculate(Body::Mars, Rashi::Mesha, 205.0, applyDegreeRefinement: false);
        $resultB = $this->calculator->calculate(Body::Mars, Rashi::Mesha, 5.0, applyDegreeRefinement: false);

        $this->assertSame(Dignity::OwnSign, $resultA);
        $this->assertSame(Dignity::OwnSign, $resultB);
        $this->assertSame($resultA, $resultB);
    }

    public function testMoonInVrishabhaWithoutDegreeIsReportedAsExalted(): void
    {
        // Vrishabha isn't the Moon's own sign at all (only Karka is), so
        // with degree refinement disabled the only sensible classification
        // is Exalted, not OwnSign.
        $this->assertSame(
            Dignity::Exalted,
            $this->calculator->calculate(Body::Moon, Rashi::Vrishabha, 100.0, applyDegreeRefinement: false)
        );
    }
}
