<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Integration\Astronomy;

use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Astronomy\Engine\PurePhpAstronomyEngine;
use Vittix\Panchang\Astronomy\Sidereal\KrishnamurtiAyanamsha;
use Vittix\Panchang\Astronomy\Sidereal\LahiriAyanamsha;
use Vittix\Panchang\Astronomy\Sidereal\RamanAyanamsha;
use Vittix\Panchang\ValueObject\AstroInstant;

final class AyanamshaTest extends TestCase
{
    private AstroInstant $j2000;

    protected function setUp(): void
    {
        // J2000.0 corresponds to Jan 1, 2000, 12:00 TT (Julian Day 2451545.0)
        $this->j2000 = new AstroInstant(
            new \DateTimeImmutable('2000-01-01 12:00:00', new \DateTimeZone('UTC')),
            2451545.0,
            2451545.0,
            0.0
        );
    }

    public function testLahiriAyanamshaAtJ2000(): void
    {
        $calculator = new LahiriAyanamsha();
        $ayanamsha = $calculator->calculate($this->j2000);
        
        // Lahiri Ayanamsha at J2000 is approximately 23.8537°
        self::assertEqualsWithDelta(23.8537, $ayanamsha, 0.001);
    }

    public function testRamanAyanamshaAtJ2000(): void
    {
        $calculator = new RamanAyanamsha();
        $ayanamsha = $calculator->calculate($this->j2000);
        
        // Raman Ayanamsha at J2000 is roughly 22.415°
        self::assertEqualsWithDelta(22.4123, $ayanamsha, 0.001);
    }

    public function testKrishnamurtiAyanamshaAtJ2000(): void
    {
        $calculator = new KrishnamurtiAyanamsha();
        $ayanamsha = $calculator->calculate($this->j2000);
        
        // KP Ayanamsha at J2000 is 23.7712°
        self::assertEqualsWithDelta(23.7712, $ayanamsha, 0.001);
    }
}
