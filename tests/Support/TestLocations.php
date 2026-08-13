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

    public static function delhi(): GeoLocation
    {
        return new GeoLocation(
            latitude: 28.7041,
            longitude: 77.1025,
            elevationMeters: 214.0
        );
    }
    public static function mumbai(): GeoLocation
    {
        return new GeoLocation(19.0760, 72.8777, 14.0);
    }

    public static function kolkata(): GeoLocation
    {
        return new GeoLocation(22.5726, 88.3639, 9.0);
    }

    public static function chennai(): GeoLocation
    {
        return new GeoLocation(13.0827, 80.2707, 6.0);
    }

    public static function bengaluru(): GeoLocation
    {
        return new GeoLocation(12.9716, 77.5946, 920.0);
    }

    public static function newYork(): GeoLocation
    {
        return new GeoLocation(40.7128, -74.0060, 10.0);
    }

    public static function london(): GeoLocation
    {
        return new GeoLocation(51.5074, -0.1278, 35.0);
    }

    public static function tokyo(): GeoLocation
    {
        return new GeoLocation(35.6762, 139.6503, 40.0);
    }

    public static function sydney(): GeoLocation
    {
        return new GeoLocation(-33.8688, 151.2093, 3.0);
    }
}
