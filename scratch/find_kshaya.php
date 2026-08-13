<?php

require __DIR__ . '/../vendor/autoload.php';

use Vittix\Panchang\Panchang;
use Vittix\Panchang\ValueObject\GeoLocation;

$panchang = Panchang::createDefault();
$ahmedabad = new GeoLocation(23.0225, 72.5714, 53.0);
$timezone = new DateTimeZone('Asia/Kolkata');

$year = 1960;
while ($year < 1990) {
    for ($month = 1; $month <= 12; $month++) {
        $date = new DateTimeImmutable("$year-$month-15", $timezone);
        $day = $panchang->day($date, $ahmedabad);
        if ($day->isKshayaMasa) {
            echo "Kshaya Masa found on {$date->format('Y-m-d')}: " . $day->lunarMonth?->nameEnglish() . "\n";
            exit(0);
        }
    }
    $year++;
}
echo "No Kshaya Masa found in range\n";
