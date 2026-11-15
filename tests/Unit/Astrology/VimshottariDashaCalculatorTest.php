<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Unit\Astrology;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Astrology\VimshottariDashaCalculator;
use Vittix\Panchang\Enum\Body;

final class VimshottariDashaCalculatorTest extends TestCase
{
    private VimshottariDashaCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new VimshottariDashaCalculator();
    }

    public function testStartsAtCorrectDashaAndFraction(): void
    {
        $birthTime = new DateTimeImmutable('2000-01-01 12:00:00', new DateTimeZone('UTC'));
        
        // Exact middle of Ashwini Nakshatra (0 - 13.3333333) -> 6.6666666
        $moonLongitude = (360.0 / 27.0) / 2.0;

        $dashas = $this->calculator->calculate($birthTime, $moonLongitude);

        $this->assertCount(9, $dashas);

        // Ashwini's lord is Ketu.
        $firstDasha = $dashas[0];
        $this->assertSame(Body::KetuMean, $firstDasha->body);
        $this->assertEquals($birthTime, $firstDasha->start);

        // Since it's exactly in the middle, Ketu's 7 years should be halved to 3.5 years.
        // 3.5 years * 31556952 seconds/year = 110449332 seconds.
        $expectedEndSeconds = $birthTime->getTimestamp() + 110449332;
        $this->assertSame($expectedEndSeconds, $firstDasha->end->getTimestamp());

        // Test Antardashas within the first Mahadasha. Birth sits 3.5 years
        // into the full 7-year Ketu Mahadasha: Ketu-Ketu, Ketu-Venus, Ketu-
        // Sun, Ketu-Moon and Ketu-Mars are entirely before birth and are
        // dropped; Ketu-Rahu (2.917–3.967y of the full timeline) is running
        // at birth and is listed first, truncated.
        $this->assertCount(4, $firstDasha->subPeriods);
        $this->assertSame(Body::RahuMean, $firstDasha->subPeriods[0]->body); // Ketu-Rahu, running at birth
        $this->assertSame(Body::Jupiter, $firstDasha->subPeriods[1]->body);
        $this->assertSame($birthTime, $firstDasha->subPeriods[0]->start);
        $this->assertEquals($firstDasha->end, $firstDasha->subPeriods[3]->end);

        // The listed Antardashas keep their FULL proportions (not scaled to
        // the 3.5 remaining years): Ketu-Jupiter = 16 × 7 / 120 years.
        $expectedJupiterSeconds = (int) round(16.0 * 7.0 / 120.0 * 31556952.0);
        $this->assertSame(
            $expectedJupiterSeconds,
            $firstDasha->subPeriods[1]->end->getTimestamp() - $firstDasha->subPeriods[1]->start->getTimestamp()
        );

        // Test the final Mahadasha correctly accounts for 120 years overall from birth minus elapsed first half
        $lastDasha = $dashas[8];
        $this->assertSame(Body::Mercury, $lastDasha->body);

        // Total duration should be (120 - 3.5) years = 116.5 years
        $totalExpectedSeconds = (int) round(116.5 * 31556952.0);
        $this->assertSame(
            $birthTime->getTimestamp() + $totalExpectedSeconds,
            $lastDasha->end->getTimestamp()
        );
    }

    /**
     * Balance-dasha Antardashas must come from the FULL-length Antardasha
     * timeline of the whole Mahadasha (starting at birth minus elapsed
     * time), not be scaled to fit the remaining time. The first listed
     * Antardasha is the one running at birth, truncated.
     */
    public function testBalanceDashaAntardashasAreNotScaledToRemainingTime(): void
    {
        $birthTime = new DateTimeImmutable('1990-05-05 10:00:00', new DateTimeZone('Asia/Kolkata'));

        // Moon in Krittika (Sun's nakshatra) with 0.227 of the nakshatra
        // traversed → Sun Mahadasha with ≈0.773 of the period remaining.
        $nakshatraLength = 360.0 / 27.0;
        $moonLongitude = 2.0 * $nakshatraLength + 0.227 * $nakshatraLength;

        $dashas = $this->calculator->calculate($birthTime, $moonLongitude);

        $sun = $dashas[0];
        $this->assertSame(Body::Sun, $sun->body);

        $remaining = ($sun->end->getTimestamp() - $birthTime->getTimestamp())
            / (6.0 * 31556952.0);
        $this->assertEqualsWithDelta(0.773, $remaining, 0.001);

        // Birth sits 1.362 years into the full Sun Mahadasha: Sun-Sun
        // (0–0.3y), Sun-Moon (0.3–0.8y) and Sun-Mars (0.8–1.15y) are gone;
        // Sun-Rahu (1.15–2.05y) is running and must be listed first,
        // truncated at birth.
        $this->assertSame(Body::RahuMean, $sun->subPeriods[0]->body);
        $this->assertEquals($birthTime, $sun->subPeriods[0]->start);

        // Later Antardashas keep the FULL proportions (years × mahadasha
        // years / 120) — Sun-Jupiter = 16 × 6 / 120 = 0.8 years.
        $expectedJupiterSeconds = (int) round(16.0 * 6.0 / 120.0 * 31556952.0);
        $this->assertSame(
            $expectedJupiterSeconds,
            $sun->subPeriods[1]->end->getTimestamp() - $sun->subPeriods[1]->start->getTimestamp()
        );
        $this->assertSame(
            $expectedJupiterSeconds,
            $sun->subPeriods[1]->end->getTimestamp() - $sun->subPeriods[0]->end->getTimestamp()
        );

        // Sequence continues from the running lord.
        $this->assertSame(
            [Body::RahuMean, Body::Jupiter, Body::Saturn, Body::Mercury, Body::KetuMean, Body::Venus],
            array_map(static fn ($p) => $p->body, $sun->subPeriods)
        );

        // The listed Antardashas must sum exactly to the remaining time.
        $sum = 0;
        foreach ($sun->subPeriods as $sub) {
            $sum += $sub->end->getTimestamp() - $sub->start->getTimestamp();
        }
        $this->assertSame($sun->end->getTimestamp() - $birthTime->getTimestamp(), $sum);
    }
}
