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
}
