<?php
require 'vendor/autoload.php';

$start = microtime(true);
$panchang = \Vittix\Panchang\Panchanga\PanchangService::createDefault();
echo "Init: " . ((microtime(true) - $start) * 1000) . " ms\n";

$start = microtime(true);
$date = new DateTimeImmutable('2024-01-01');
$location = new \Vittix\Panchang\ValueObject\GeoLocation(28.6, 77.2);
$panchang->getForDate($date, $location);
echo "First call: " . ((microtime(true) - $start) * 1000) . " ms\n";

$start = microtime(true);
$date = new DateTimeImmutable('2024-01-02');
$panchang->getForDate($date, $location);
echo "Second call: " . ((microtime(true) - $start) * 1000) . " ms\n";
