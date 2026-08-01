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

        // Test Antardashas within the first Mahadasha
        $this->assertCount(9, $firstDasha->subPeriods);
        $this->assertSame(Body::KetuMean, $firstDasha->subPeriods[0]->body); // Ketu-Ketu
        $this->assertSame(Body::Venus, $firstDasha->subPeriods[1]->body); // Ketu-Venus
        $this->assertSame($birthTime, $firstDasha->subPeriods[0]->start);
        $this->assertEquals($firstDasha->end, $firstDasha->subPeriods[8]->end);

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
}
