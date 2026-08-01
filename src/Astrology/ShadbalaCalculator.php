<?php

declare(strict_types=1);

namespace Vittix\Panchang\Astrology;

use Vittix\Panchang\Astrology\Shadbala\DikBalaCalculator;
use Vittix\Panchang\Astrology\Shadbala\NaisargikaBalaCalculator;
use Vittix\Panchang\Astrology\Shadbala\SthanaBalaCalculator;
use Vittix\Panchang\Enum\Body;
use Vittix\Panchang\ValueObject\Kundali;
use Vittix\Panchang\ValueObject\Shadbala\Shadbala;
use Vittix\Panchang\ValueObject\Shadbala\ShadbalaValues;

/**
 * Calculates the Shadbala (Six-fold strength) of the 7 visible planets.
 */
final class ShadbalaCalculator
{
    private const VISIBLE_PLANETS = [
        Body::Sun,
        Body::Moon,
        Body::Mars,
        Body::Mercury,
        Body::Jupiter,
        Body::Venus,
        Body::Saturn,
    ];

    public function __construct(
        private readonly SthanaBalaCalculator $sthanaBala,
        private readonly DikBalaCalculator $dikBala,
        private readonly NaisargikaBalaCalculator $naisargikaBala,
    ) {
    }

    public function calculate(Kundali $d1Kundali): Shadbala
    {
        $planets = [];

        foreach (self::VISIBLE_PLANETS as $body) {
            $placement = $d1Kundali->placements[$body->value] ?? null;

            if ($placement === null) {
                continue; // Should not happen in a complete Kundali
            }

            $sthana = $this->sthanaBala->calculate($placement);
            $dik = $this->dikBala->calculate($placement);
            $naisargika = $this->naisargikaBala->calculate($body);
            
            // Kala, Chesta, and Drik Bala will return 0.0 until we implement 
            // the required astronomical primitives (declination, speed, aspects logic).
            $kala = 0.0;
            $chesta = 0.0;
            $drik = 0.0;

            $planets[$body->value] = new ShadbalaValues(
                body: $body,
                sthanaBala: $sthana,
                dikBala: $dik,
                kalaBala: $kala,
                chestaBala: $chesta,
                naisargikaBala: $naisargika,
                drikBala: $drik
            );
        }

        return new Shadbala($planets);
    }
}
