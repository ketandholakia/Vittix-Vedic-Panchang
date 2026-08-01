<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Unit\Astrology\Yoga;

use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Astrology\Yoga\PanchaMahapurusha\RuchakaYoga;
use Vittix\Panchang\Enum\Body;
use Vittix\Panchang\Enum\Dignity;
use Vittix\Panchang\Enum\Rashi;
use Vittix\Panchang\ValueObject\AscendantPosition;
use Vittix\Panchang\ValueObject\Kundali;
use Vittix\Panchang\ValueObject\PlanetPlacement;

class PanchaMahapurushaYogaTest extends TestCase
{
    private RuchakaYoga $ruchaka;

    protected function setUp(): void
    {
        $this->ruchaka = new RuchakaYoga();
    }

    public function testDetectsRuchakaWhenMarsInKendraAndExalted(): void
    {
        $ascendant = new AscendantPosition(15.0, 15.0, Rashi::Mesha, 15.0);
        
        // Mars in Capricorn (10th from Aries) -> Kendra
        // Capricorn is Mars exaltation -> Exalted
        $mars = new PlanetPlacement(Body::Mars, 0.0, Rashi::Makara, 10, [], Dignity::Exalted);
        
        $placements = [Body::Mars->value => $mars];
        $kundali = new Kundali($ascendant, $placements);
        
        $yoga = $this->ruchaka->detect($kundali);

        $this->assertNotNull($yoga);
        $this->assertEquals('Ruchaka Yoga', $yoga->name);
    }

    public function testDoesNotDetectRuchakaWhenMarsInApoklima(): void
    {
        $ascendant = new AscendantPosition(15.0, 15.0, Rashi::Mesha, 15.0);
        
        // Mars in Scorpio (8th from Aries) -> Not Kendra
        // Scorpio is Mars Own Sign -> OwnSign (Strong, but wrong house)
        $mars = new PlanetPlacement(Body::Mars, 0.0, Rashi::Vrischika, 8, [], Dignity::OwnSign);
        
        $placements = [Body::Mars->value => $mars];
        $kundali = new Kundali($ascendant, $placements);
        
        $yoga = $this->ruchaka->detect($kundali);

        $this->assertNull($yoga);
    }
}
