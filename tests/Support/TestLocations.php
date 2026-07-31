<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Support;

use Vittix\Panchang\ValueObject\GeoLocation;

final class TestLocations
{
    public static function ahmedabad(): GeoLocation
    {
        return new GeoLocation(
            latitude: 23.0225,
            longitude: 72.5714,
            elevationMeters: 53.0
        );
    }
}
