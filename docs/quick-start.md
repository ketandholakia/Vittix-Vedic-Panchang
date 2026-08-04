# Quick Start

```php
<?php

declare(strict_types=1);

use DateTimeImmutable;
use DateTimeZone;
use Vittix\Panchang\Panchang;
use Vittix\Panchang\ValueObject\GeoLocation;

$panchang = Panchang::createDefault();
$date = new DateTimeImmutable('2026-08-04', new DateTimeZone('Asia/Kolkata'));
$location = new GeoLocation(latitude: 28.6139, longitude: 77.2090);

$day = $panchang->day($date, $location);

echo $day->tithiAtSunrise->nameEnglish();
echo PHP_EOL;
echo $day->solarEvents->sunrise?->format('H:i:s');
```

For Kundli generation:

```php
$moment = new DateTimeImmutable('1990-01-01 06:30:00', new DateTimeZone('Asia/Kolkata'));
$kundli = $panchang->kundali($moment, $location);

echo $kundli->ascendant->rashi->nameEnglish();
```
