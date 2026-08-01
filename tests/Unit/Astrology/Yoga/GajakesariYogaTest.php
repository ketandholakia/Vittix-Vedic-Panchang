<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Unit\Astrology\Yoga;

use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Astrology\Yoga\GajakesariYoga;
use Vittix\Panchang\Enum\Body;
use Vittix\Panchang\Enum\Rashi;
use Vittix\Panchang\ValueObject\AscendantPosition;
use Vittix\Panchang\ValueObject\Kundali;
use Vittix\Panchang\ValueObject\PlanetPlacement;

class GajakesariYogaTest extends TestCase
{
    private GajakesariYoga $rule;

    protected function setUp(): void
    {
        $this->rule = new GajakesariYoga();
    }

    public function testDetectsGajakesariWhenJupiterInKendraFromMoon(): void
    {
        $ascendant = new AscendantPosition(15.0, 15.0, Rashi::Mesha, 15.0);
        
        // Moon in Aries (Rashi index 0)
        // Jupiter in Cancer (Rashi index 3) -> 4th from Moon = Kendra
        $placements = [
            Body::Moon->value => new PlanetPlacement(Body::Moon, 0.0, Rashi::Mesha, 1),
            Body::Jupiter->value => new PlanetPlacement(Body::Jupiter, 0.0, Rashi::Karka, 4),
        ];

        $kundali = new Kundali($ascendant, $placements);
        $yoga = $this->rule->detect($kundali);

        $this->assertNotNull($yoga);
        $this->assertEquals('Gajakesari Yoga', $yoga->name);
    }

    public function testDoesNotDetectWhenJupiterNotInKendra(): void
    {
        $ascendant = new AscendantPosition(15.0, 15.0, Rashi::Mesha, 15.0);
        
        // Moon in Aries (0)
        // Jupiter in Leo (4) -> 5th from Moon = Not Kendra
        $placements = [
            Body::Moon->value => new PlanetPlacement(Body::Moon, 0.0, Rashi::Mesha, 1),
            Body::Jupiter->value => new PlanetPlacement(Body::Jupiter, 0.0, Rashi::Simha, 5),
        ];

        $kundali = new Kundali($ascendant, $placements);
        $yoga = $this->rule->detect($kundali);

        $this->assertNull($yoga);
    }
}
