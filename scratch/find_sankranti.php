<?php
require 'vendor/autoload.php';
use Vittix\Panchang\Panchanga\TransitionFinder;
use Vittix\Panchang\Astronomy\Engine\PurePhpAstronomyEngine;
use Vittix\Panchang\Enum\Body;
use Vittix\Panchang\Time\AstroTimeFactory;
use Vittix\Panchang\ValueObject\AstroInstant;
use Vittix\Panchang\Math\Angle;

use Vittix\Panchang\Time\JulianDay;

$engine = new PurePhpAstronomyEngine();
$finder = new TransitionFinder($engine);
$factory = new AstroTimeFactory();

$tz = new DateTimeZone('Asia/Kolkata');

// Mesha Sankranti 2024 is around April 13.
$from = $factory->fromDateTime(new DateTimeImmutable('2024-04-12 00:00:00', $tz));
$until = $factory->fromDateTime(new DateTimeImmutable('2024-04-15 00:00:00', $tz));

$fn = function(AstroInstant $instant) use ($engine) {
    $sun = $engine->siderealLongitude(Body::Sun, $instant);
    return (int) floor(Angle::normalize($sun) / 30.0);
};

$result = $finder->find($from, $until, $fn);
if ($result) {
    echo "Mesha Sankranti 2024: " . JulianDay::toUtc($result->julianDayUt)->setTimezone($tz)->format('Y-m-d H:i:s') . PHP_EOL;
}

// Makara Sankranti 2024 is around Jan 14/15.
$from2 = $factory->fromDateTime(new DateTimeImmutable('2024-01-14 00:00:00', $tz));
$until2 = $factory->fromDateTime(new DateTimeImmutable('2024-01-16 00:00:00', $tz));

$result2 = $finder->find($from2, $until2, $fn);
if ($result2) {
    echo "Makara Sankranti 2024: " . JulianDay::toUtc($result2->julianDayUt)->setTimezone($tz)->format('Y-m-d H:i:s') . PHP_EOL;
}
