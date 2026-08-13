<?php
require 'vendor/autoload.php';
use Vittix\Panchang\Panchang;
use Vittix\Panchang\ValueObject\GeoLocation;

$p = Panchang::createDefault();
$loc = new GeoLocation(23.17, 75.78);
$tz = new DateTimeZone('Asia/Kolkata');
for($m=1; $m<=12; $m++) {
    $d = new DateTimeImmutable("2042-$m-15", $tz);
    $res = $p->day($d, $loc);
    if($res->isAdhikaMasa) echo $d->format('Y-m-d').' is Adhika '.$res->lunarMonth->name.PHP_EOL;
}
