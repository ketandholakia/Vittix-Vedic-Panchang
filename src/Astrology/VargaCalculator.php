<?php

declare(strict_types=1);

namespace Vittix\Panchang\Astrology;

use Vittix\Panchang\Astrology\Varga\DashamshaMapping;
use Vittix\Panchang\Astrology\Varga\DrekkanaMapping;
use Vittix\Panchang\Astrology\Varga\HoraMapping;
use Vittix\Panchang\Astrology\Varga\NavamshaMapping;
use Vittix\Panchang\Astrology\Varga\RasiMapping;
use Vittix\Panchang\Astrology\Varga\ChaturthamshaMapping;
use Vittix\Panchang\Astrology\Varga\SaptamshaMapping;
use Vittix\Panchang\Astrology\Varga\DwadashamshaMapping;
use Vittix\Panchang\Astrology\Varga\ShodashamshaMapping;
use Vittix\Panchang\Astrology\Varga\VimshamshaMapping;
use Vittix\Panchang\Astrology\Varga\ChaturvimshamshaMapping;
use Vittix\Panchang\Astrology\Varga\SaptavimshamshaMapping;
use Vittix\Panchang\Astrology\Varga\TrimshamshaMapping;
use Vittix\Panchang\Astrology\Varga\KhavedamshaMapping;
use Vittix\Panchang\Astrology\Varga\AkshavedamshaMapping;
use Vittix\Panchang\Astrology\Varga\ShashtiamshaMapping;
use Vittix\Panchang\Astrology\Varga\VargaMappingInterface;
use Vittix\Panchang\Enum\Varga;
use Vittix\Panchang\ValueObject\AscendantPosition;
use Vittix\Panchang\ValueObject\Kundali;
use Vittix\Panchang\ValueObject\PlanetPlacement;

/**
 * Calculates a Divisional Chart (Varga) by remapping the base Kundali.
 */
final class VargaCalculator
{
    public function __construct(
        private readonly DignityCalculator $dignityCalculator,
        private readonly AspectCalculator $aspectCalculator,
    ) {
    }

    public function calculate(Kundali $baseKundali, Varga $varga): Kundali
    {
        if ($varga === Varga::D1) {
            return $baseKundali;
        }

        $mapping = $this->getMapping($varga);

        // Map Ascendant
        $vargaAscendantRashi = $mapping->getRashi($baseKundali->ascendant->siderealLongitude);
        $vargaAscendant = new AscendantPosition(
            tropicalLongitude: $baseKundali->ascendant->tropicalLongitude,
            siderealLongitude: $baseKundali->ascendant->siderealLongitude, // keep original longitude
            rashi: $vargaAscendantRashi,
            degreeInSign: fmod($baseKundali->ascendant->siderealLongitude, 30.0) // original degree
        );
        $lagnaRashiIndex = $vargaAscendantRashi->value;

        // Map Planets
        $vargaPlacements = [];
        foreach ($baseKundali->placements as $bodyValue => $basePlacement) {
            $body = $basePlacement->body;
            $longitude = $basePlacement->longitude;
            
            $vargaRashi = $mapping->getRashi($longitude);
            $rashiIndex = $vargaRashi->value;

            // Recalculate House based on Varga Lagna
            $vargaHouse = (($rashiIndex - $lagnaRashiIndex + 12) % 12) + 1;

            // Recalculate Dignity and Aspects in the new Varga
            $dignity = $this->dignityCalculator->calculate($body, $vargaRashi, $longitude);
            $aspectsHouses = $this->aspectCalculator->calculateAspectedHouses($body, $vargaHouse);

            $vargaPlacements[$bodyValue] = new PlanetPlacement(
                body: $body,
                longitude: $longitude, // keep original longitude
                rashi: $vargaRashi,
                house: $vargaHouse,
                aspectsHouses: $aspectsHouses,
                dignity: $dignity
            );
        }

        return new Kundali($vargaAscendant, $vargaPlacements);
    }

    private function getMapping(Varga $varga): VargaMappingInterface
    {
        return match ($varga) {
            Varga::D1 => new RasiMapping(),
            Varga::D2 => new HoraMapping(),
            Varga::D3 => new DrekkanaMapping(),
            Varga::D4 => new ChaturthamshaMapping(),
            Varga::D7 => new SaptamshaMapping(),
            Varga::D9 => new NavamshaMapping(),
            Varga::D10 => new DashamshaMapping(),
            Varga::D12 => new DwadashamshaMapping(),
            Varga::D16 => new ShodashamshaMapping(),
            Varga::D20 => new VimshamshaMapping(),
            Varga::D24 => new ChaturvimshamshaMapping(),
            Varga::D27 => new SaptavimshamshaMapping(),
            Varga::D30 => new TrimshamshaMapping(),
            Varga::D40 => new KhavedamshaMapping(),
            Varga::D45 => new AkshavedamshaMapping(),
            Varga::D60 => new ShashtiamshaMapping(),
        };
    }
}
