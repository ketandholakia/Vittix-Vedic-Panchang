<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Integration;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Panchang;
use Vittix\Panchang\Tests\Support\TestLocations;

final class LocationChangeTest extends TestCase
{
    public function testSunriseAndTithiEndDifferByLocation(): void
    {
        $panchang = Panchang::createDefault();
        $date = new DateTimeImmutable('2026-08-05', new DateTimeZone('Asia/Kolkata'));
        
        $ahmedabad = TestLocations::ahmedabad();
        $delhi = TestLocations::delhi();

        $dayAhmedabad = $panchang->day($date, $ahmedabad);
        $dayDelhi = $panchang->day($date, $delhi);

        self::assertNotNull($dayAhmedabad->solarEvents->sunrise);
        self::assertNotNull($dayDelhi->solarEvents->sunrise);
        self::assertNotNull($dayAhmedabad->tithiEndsAt);
        self::assertNotNull($dayDelhi->tithiEndsAt);

        // Assert sunrise times are different
        self::assertNotSame(
            $dayAhmedabad->solarEvents->sunrise->getTimestamp(),
            $dayDelhi->solarEvents->sunrise->getTimestamp(),
            'Sunrise time should differ between Ahmedabad and Delhi'
        );

        // Assert tithi ends at times are exactly the same globally (within tolerance)
        self::assertEqualsWithDelta(
            $dayAhmedabad->tithiEndsAt->getTimestamp(),
            $dayDelhi->tithiEndsAt->getTimestamp(),
            5,
            'Tithi end time is a cosmic event and must be identical across locations'
        );
    }
}
