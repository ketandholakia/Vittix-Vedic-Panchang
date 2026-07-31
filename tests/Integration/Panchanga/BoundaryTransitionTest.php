<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Integration\Panchanga;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Astronomy\Contracts\AstronomyEngineInterface;
use Vittix\Panchang\Enum\Body;
use Vittix\Panchang\Enum\Tithi;
use Vittix\Panchang\Panchanga\KaranaCalculator;
use Vittix\Panchang\Panchanga\NakshatraCalculator;
use Vittix\Panchang\Panchanga\PanchangaCalculator;
use Vittix\Panchang\Panchanga\TithiCalculator;
use Vittix\Panchang\Panchanga\TransitionFinder;
use Vittix\Panchang\Panchanga\VaraCalculator;
use Vittix\Panchang\Panchanga\YogaCalculator;
use Vittix\Panchang\ValueObject\AstroInstant;
use Vittix\Panchang\ValueObject\EclipticPosition;
use Vittix\Panchang\ValueObject\GeoLocation;
use Vittix\Panchang\ValueObject\SolarEvents;
use Vittix\Panchang\Calendar\LunarMonthCalculator;
use Vittix\Panchang\Calendar\AdhikaMasaCalculator;

/**
 * A mock engine for boundary testing that returns specific longitudes
 * and hardcoded solar events based on the time difference from a "sunrise" epoch.
 */
class BoundaryMockEngine implements AstronomyEngineInterface
{
    // JD of our mock sunrise
    public float $sunriseJd;
    
    // Tithi transition happens at sunriseJd + transitionOffsetJd
    public float $transitionOffsetJd = 0.0;

    public function position(Body $body, AstroInstant $instant): EclipticPosition
    {
        return new EclipticPosition($this->tropicalLongitude($body, $instant), 0, 1.0);
    }

    public function tropicalLongitude(Body $body, AstroInstant $instant): float
    {
        return $this->siderealLongitude($body, $instant);
    }

    public function siderealLongitude(Body $body, AstroInstant $instant): float
    {
        // Sun is always at 0 degrees
        if ($body === Body::Sun) {
            return 0.0;
        }
        
        // Moon moves exactly 12 degrees when it hits the transition time
        $jd = $instant->julianDayUt;
        $transitionTime = $this->sunriseJd + $this->transitionOffsetJd;
        
        // We simulate that exactly at $transitionTime, Moon reaches 12.0 degrees (end of Pratipada).
        // Before that it's < 12, after that it's > 12.
        // Let's say Moon speed is 12 degrees per day.
        $daysFromTransition = $jd - $transitionTime;
        return 12.0 + ($daysFromTransition * 12.0);
    }

    public function ayanamsa(AstroInstant $instant): float
    {
        return 0.0;
    }

    public function solarEvents(DateTimeImmutable $localDate, GeoLocation $location, DateTimeZone $timezone): SolarEvents
    {
        // Just return 06:00 and 18:00 on the requested $localDate
        $sunriseDt = $localDate->setTime(6, 0);
        $sunsetDt = $localDate->setTime(18, 0);
        
        return new SolarEvents($sunriseDt, $sunsetDt, null);
    }
}

final class BoundaryTransitionTest extends TestCase
{
    private PanchangaCalculator $calculator;
    private BoundaryMockEngine $engine;

    protected function setUp(): void
    {
        $this->engine = new BoundaryMockEngine();
        $finder = new TransitionFinder($this->engine);
        
        $lunarMonthCalc = new LunarMonthCalculator($this->engine);
        $adhikaCalc = new AdhikaMasaCalculator($this->engine, $lunarMonthCalc);
        
        $this->calculator = new PanchangaCalculator(
            $this->engine,
            new TithiCalculator($this->engine),
            new NakshatraCalculator($this->engine),
            new YogaCalculator($this->engine),
            new KaranaCalculator($this->engine),
            new VaraCalculator(),
            $finder,
            $lunarMonthCalc,
            $adhikaCalc
        );
    }

    public function testTithiEnds30SecondsBeforeSunrise(): void
    {
        $dt = new DateTimeImmutable('2026-01-01 06:00:00', new DateTimeZone('Asia/Kolkata'));
        $factory = new \Vittix\Panchang\Time\AstroTimeFactory();
        $this->engine->sunriseJd = $factory->fromDateTime($dt)->julianDayUt;
        
        // Tithi ends 30 seconds before sunrise (30s = 30 / 86400 days)
        $this->engine->transitionOffsetJd = -(30.0 / 86400.0);
        
        $panchang = $this->calculator->forDay($dt, new GeoLocation(0, 0));
        
        // At sunrise, the Tithi should be Dwitiya (since Pratipada ended 30s ago)
        self::assertSame(Tithi::ShuklaD2, $panchang->tithiAtSunrise);
        
        // Dwitiya ends roughly 1 day later (in our mock engine, Moon moves 12 deg/day)
        self::assertNotNull($panchang->tithiEndsAt);
    }

    public function testTithiEndsExactlyAtSunrise(): void
    {
        $dt = new DateTimeImmutable('2026-01-01 06:00:00', new DateTimeZone('Asia/Kolkata'));
        $factory = new \Vittix\Panchang\Time\AstroTimeFactory();
        $this->engine->sunriseJd = $factory->fromDateTime($dt)->julianDayUt;
        
        // Tithi ends exactly at sunrise
        $this->engine->transitionOffsetJd = 0.0;
        
        $panchang = $this->calculator->forDay($dt, new GeoLocation(0, 0));
        
        // Technically Pratipada ends exactly at sunrise. 
        // In Panchanga, if elongation is >= 12.0, it's the next Tithi.
        // So at exactly 12.0, it's Dwitiya.
        self::assertSame(Tithi::ShuklaD2, $panchang->tithiAtSunrise);
        
        // And it should end exactly 1 day later (since Moon speed is 12 deg/day)
        $tithiEndsAt = $panchang->tithiEndsAt;
        self::assertNotNull($tithiEndsAt);
        self::assertEqualsWithDelta($dt->getTimestamp() + 86400, $tithiEndsAt->getTimestamp(), 5);
    }

    public function testTithiEnds30SecondsAfterSunrise(): void
    {
        $dt = new DateTimeImmutable('2026-01-01 06:00:00', new DateTimeZone('Asia/Kolkata'));
        $factory = new \Vittix\Panchang\Time\AstroTimeFactory();
        $this->engine->sunriseJd = $factory->fromDateTime($dt)->julianDayUt;
        
        // Tithi ends 30 seconds after sunrise
        $this->engine->transitionOffsetJd = (30.0 / 86400.0);
        
        $panchang = $this->calculator->forDay($dt, new GeoLocation(0, 0));
        
        // It ends 30 seconds later (with up to 3 seconds of binary search tolerance)
        $tithiEndsAt = $panchang->tithiEndsAt;
        self::assertNotNull($tithiEndsAt);
        self::assertEqualsWithDelta($dt->getTimestamp() + 30, $tithiEndsAt->getTimestamp(), 5);
    }
}
