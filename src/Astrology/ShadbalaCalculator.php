<?php

declare(strict_types=1);

namespace Vittix\Panchang\Astrology;

use Vittix\Panchang\Astrology\Shadbala\AyanaBalaCalculator;
use Vittix\Panchang\Astrology\Shadbala\ChestaBalaCalculator;
use Vittix\Panchang\Astrology\Shadbala\DikBalaCalculator;
use Vittix\Panchang\Astrology\Shadbala\NaisargikaBalaCalculator;
use Vittix\Panchang\Astrology\Shadbala\SthanaBalaCalculator;
use Vittix\Panchang\Enum\Body;
use Vittix\Panchang\ValueObject\Kundali;
use Vittix\Panchang\ValueObject\Shadbala\Shadbala;
use Vittix\Panchang\ValueObject\Shadbala\ShadbalaValues;

/**
 * Calculates the Shadbala (Six-fold strength) of the 7 visible planets.
 *
 * ⚠️ PARTIAL IMPLEMENTATION — not the full classical Shadbala:
 *  - Sthana, Dik, Naisargika, Ayana and Chesta Bala are implemented
 *    (Chesta Bala as a simplified model).
 *  - Kala Bala is *approximated* by Ayana Bala only; the Paksha, Vara,
 *    Hora, Maasa and Drekkana sub-components are not yet computed.
 *  - Drik Bala (aspect-based strength) is not implemented and always
 *    returns 0.0.
 *
 * Consumers should treat the `kalaBala` and `drikBala` values as
 * placeholders until these components are completed.
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
        private readonly AyanaBalaCalculator $ayanaBala,
        private readonly ChestaBalaCalculator $chestaBala,
    ) {
    }

    public function calculate(Kundali $d1Kundali): Shadbala
    {
        /** @var array<string, ShadbalaValues> $planets */
        $planets = [];

        foreach (self::VISIBLE_PLANETS as $body) {
            $placement = $d1Kundali->placements[$body->value] ?? null;

            if ($placement === null) {
                continue; // Should not happen in a complete Kundali
            }

            $sthana = $this->sthanaBala->calculate($placement);
            $dik = $this->dikBala->calculate($placement);
            $naisargika = $this->naisargikaBala->calculate($body);
            
            // Ayana Bala is a sub-component of Kala Bala.
            // For now, we will inject it directly as the Kala Bala proxy,
            // since true Kala Bala requires Paksha, Vara, Hora, etc.
            $kala = $this->ayanaBala->calculate($placement);
            
            $chesta = $this->chestaBala->calculate($placement);
            
            // Drik Bala requires aspects logic
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


