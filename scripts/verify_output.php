<?php
require 'vendor/autoload.php';
$p = \Vittix\Panchang\Panchang::createDefault();
$d = $p->day(
    new \DateTimeImmutable('2026-07-31', new \DateTimeZone('Asia/Kolkata')),
    \Vittix\Panchang\Tests\Support\TestLocations::ahmedabad()
);
echo sprintf(
    "%s · %s · %s Pada %d\nYoga: %s · Karana: %s · Vara: %s\nSunrise: %s IST · Sunset: %s IST\nTithi ends: %s · Lunar month: %s\n",
    $d->tithiAtSunrise->nameEnglish(),
    $d->paksha->label(),
    $d->nakshatraAtSunrise->nameEnglish(),
    $d->padaAtSunrise,
    $d->yogaAtSunrise->nameEnglish(),
    $d->karanaAtSunrise->nameEnglish(),
    $d->vara->nameEnglish(),
    $d->solarEvents->sunrise?->format('H:i'),
    $d->solarEvents->sunset?->format('H:i'),
    $d->tithiEndsAt?->format('H:i'),
    $d->lunarMonth?->nameEnglish()
);
