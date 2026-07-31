<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Astronomy\Engine\PurePhpAstronomyEngine;
use Vittix\Panchang\Enum\Body;
use Vittix\Panchang\Time\AstroTimeFactory;
use Vittix\Panchang\Time\JulianDay;

final class RawAstronomyOracleTest extends TestCase
{
    private PurePhpAstronomyEngine $engine;
    private AstroTimeFactory $timeFactory;

    protected function setUp(): void
    {
        $this->engine = new PurePhpAstronomyEngine();
        $this->timeFactory = new AstroTimeFactory();
    }

    public function testJ2000RawAstronomy(): void
    {
        // 2000-01-01 12:00:00 TT is JD 2451545.0
        // We will pass this as UT so it translates roughly to TT near JD 2451545
        // Wait, JulianDay::J2000 is 2451545.0
        $instant = $this->timeFactory->fromJulianDay(JulianDay::J2000);

        // Reference values for J2000.0 (approximate expected from standard algorithms)
        // Values from Meeus / Swiss Ephemeris roughly
        
        $sunTrop = $this->engine->tropicalLongitude(Body::Sun, $instant);
        $moonTrop = $this->engine->tropicalLongitude(Body::Moon, $instant);
        $ayanamsha = $this->engine->ayanamsa($instant);
        $sunSidereal = $this->engine->siderealLongitude(Body::Sun, $instant);
        $moonSidereal = $this->engine->siderealLongitude(Body::Moon, $instant);

        // Sun tropical at J2000.0
        self::assertEqualsWithDelta(280.37, $sunTrop, 0.05, 'Sun tropical longitude mismatch');

        // Moon tropical at J2000.0
        self::assertEqualsWithDelta(223.32, $moonTrop, 0.05, 'Moon tropical longitude mismatch');

        // Lahiri Ayanamsha at J2000.0 should be ~23.8537°
        self::assertEqualsWithDelta(23.8537, $ayanamsha, 0.001, 'Ayanamsha mismatch');

        // Sidereal tests
        self::assertEqualsWithDelta($sunTrop - $ayanamsha, $sunSidereal, 0.0001, 'Sun sidereal math mismatch');
        self::assertEqualsWithDelta(
            fmod($moonTrop - $ayanamsha + 360, 360),
            $moonSidereal,
            0.0001,
            'Moon sidereal math mismatch'
        );
    }
}
