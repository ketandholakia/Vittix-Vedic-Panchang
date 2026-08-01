<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Unit\Astrology;

use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Astrology\YogaDetector;
use Vittix\Panchang\Enum\Body;
use Vittix\Panchang\Enum\Dignity;
use Vittix\Panchang\Enum\Rashi;
use Vittix\Panchang\ValueObject\AscendantPosition;
use Vittix\Panchang\ValueObject\Kundali;
use Vittix\Panchang\ValueObject\PlanetPlacement;

class YogaDetectorTest extends TestCase
{
    private YogaDetector $detector;

    protected function setUp(): void
    {
        $this->detector = new YogaDetector();
    }

    public function testDetectsMultipleYogas(): void
    {
        $ascendant = new AscendantPosition(15.0, 15.0, Rashi::Mesha, 15.0);
        
        $moon = new PlanetPlacement(Body::Moon, 0.0, Rashi::Mesha, 1);
        
        // Jupiter in Cancer (4th house) -> Kendra from Moon = Gajakesari
        // Jupiter in Cancer -> Exalted in Kendra = Hamsa Yoga
        $jupiter = new PlanetPlacement(Body::Jupiter, 0.0, Rashi::Karka, 4, [], Dignity::Exalted);

        $placements = [
            Body::Moon->value => $moon,
            Body::Jupiter->value => $jupiter,
        ];
        
        $kundali = new Kundali($ascendant, $placements);
        
        $yogas = $this->detector->detect($kundali);

        $this->assertCount(2, $yogas);
        $names = array_map(fn($y) => $y->name, $yogas);
        
        $this->assertContains('Gajakesari Yoga', $names);
        $this->assertContains('Hamsa Yoga', $names);
    }
}
