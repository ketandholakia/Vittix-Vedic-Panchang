<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Integration\Astronomy;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Astronomy\Engine\PurePhpAstronomyEngine;
use Vittix\Panchang\Enum\Body;
use Vittix\Panchang\Math\Angle;
use Vittix\Panchang\Panchanga\TransitionFinder;
use Vittix\Panchang\Time\AstroTimeFactory;
use Vittix\Panchang\Time\JulianDay;
use Vittix\Panchang\ValueObject\AstroInstant;

final class SankrantiMomentTest extends TestCase
{
    private PurePhpAstronomyEngine $engine;
    private TransitionFinder $finder;
    private AstroTimeFactory $factory;
    private DateTimeZone $tz;

    protected function setUp(): void
    {
        $this->engine = new PurePhpAstronomyEngine();
        $this->finder = new TransitionFinder($this->engine);
        $this->factory = new AstroTimeFactory();
        $this->tz = new DateTimeZone('Asia/Kolkata');
    }

    public function testMeshaSankranti2024(): void
    {
        $from = $this->factory->fromDateTime(new DateTimeImmutable('2024-04-12 00:00:00', $this->tz));
        $until = $this->factory->fromDateTime(new DateTimeImmutable('2024-04-15 00:00:00', $this->tz));

        $fn = function(AstroInstant $instant): int {
            $sun = $this->engine->siderealLongitude(Body::Sun, $instant);
            return (int) floor(Angle::normalize($sun) / 30.0);
        };

        $result = $this->finder->find($from, $until, $fn);
        self::assertNotNull($result, 'Could not find Mesha Sankranti transition');

        $dt = JulianDay::toUtc($result->julianDayUt)->setTimezone($this->tz);
        
        // Internal engine value
        $expected = new DateTimeImmutable('2024-04-13 20:58:32', $this->tz);
        
        $diff = abs($dt->getTimestamp() - $expected->getTimestamp());
        self::assertLessThanOrEqual(60, $diff, "Mesha Sankranti should be within 60s of reference");
    }

    public function testMakaraSankranti2024(): void
    {
        $from = $this->factory->fromDateTime(new DateTimeImmutable('2024-01-14 00:00:00', $this->tz));
        $until = $this->factory->fromDateTime(new DateTimeImmutable('2024-01-16 00:00:00', $this->tz));

        $fn = function(AstroInstant $instant): int {
            $sun = $this->engine->siderealLongitude(Body::Sun, $instant);
            return (int) floor(Angle::normalize($sun) / 30.0);
        };

        $result = $this->finder->find($from, $until, $fn);
        self::assertNotNull($result, 'Could not find Makara Sankranti transition');

        $dt = JulianDay::toUtc($result->julianDayUt)->setTimezone($this->tz);
        
        // Internal engine value
        $expected = new DateTimeImmutable('2024-01-15 02:38:37', $this->tz);
        
        $diff = abs($dt->getTimestamp() - $expected->getTimestamp());
        self::assertLessThanOrEqual(60, $diff, "Makara Sankranti should be within 60s of reference");
    }
}
