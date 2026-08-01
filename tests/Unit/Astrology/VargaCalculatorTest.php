<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Unit\Astrology;

use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Astrology\AspectCalculator;
use Vittix\Panchang\Astrology\DignityCalculator;
use Vittix\Panchang\Astrology\VargaCalculator;
use Vittix\Panchang\Enum\Body;
use Vittix\Panchang\Enum\Rashi;
use Vittix\Panchang\Enum\Varga;
use Vittix\Panchang\ValueObject\AscendantPosition;
use Vittix\Panchang\ValueObject\Kundali;
use Vittix\Panchang\ValueObject\PlanetPlacement;

final class VargaCalculatorTest extends TestCase
{
    private VargaCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new VargaCalculator(new DignityCalculator(), new AspectCalculator());
    }

    public function testNavamshaMapping(): void
    {
        // Setup a mock D1 Kundali
        $ascendant = new AscendantPosition(15.0, 15.0, Rashi::Mesha, 15.0); // 15 Aries
        
        $placements = [
            Body::Sun->value => new PlanetPlacement(
                body: Body::Sun,
                longitude: 134.0, // 14 degrees Leo
                rashi: Rashi::Simha,
                house: 5
            ),
        ];
        
        $baseKundali = new Kundali($ascendant, $placements);

        $d9Kundali = $this->calculator->calculate($baseKundali, Varga::D9);

        // Ascendant: 15 Aries. Aries starts Navamshas from Aries.
        // 15 / 3.3333 = 4.5. Index 4 = Leo (Simha)
        $this->assertSame(Rashi::Simha, $d9Kundali->ascendant->rashi);

        // Sun: 134 degrees (14 Leo). Leo starts Navamshas from Aries.
        // 134 / 3.3333333 = 40.2
        // 40 % 12 = 4 -> Leo. Wait!
        // Let's manually calculate Navamsha mapping:
        // 134 / (30/9) = 134 / 3.3333333 = 40.2
        // 40 % 12 = 4 (Simha / Leo).
        // Let's verify rule: Leo 14 degrees.
        // 0-3.33 Aries, 3.33-6.66 Taurus, 6.66-10 Gemini, 10-13.33 Cancer, 13.33-16.66 Leo.
        // Yes, 14 deg Leo is in Leo Navamsha!
        $sunD9 = $d9Kundali->placements[Body::Sun->value];
        $this->assertSame(Rashi::Simha, $sunD9->rashi);
        
        // Since Ascendant is Leo and Sun is Leo, Sun should be in House 1
        $this->assertSame(1, $sunD9->house);
    }

    public function testShodashamshaMapping(): void
    {
        $ascendant = new AscendantPosition(15.0, 15.0, Rashi::Mesha, 15.0); // 15 Aries
        
        $placements = [
            Body::Moon->value => new PlanetPlacement(
                body: Body::Moon,
                longitude: 195.0, // 15 degrees Libra
                rashi: Rashi::Tula,
                house: 7
            ),
        ];
        
        $baseKundali = new Kundali($ascendant, $placements);

        $d16Kundali = $this->calculator->calculate($baseKundali, Varga::D16);

        // Moon is at 195 degrees. Rashi is 6 (Libra, Movable).
        // Modality of Libra (6) is 6 % 3 = 0 (Movable). Starts from Aries (0).
        // 15 degrees in sign / (30/16) = 15 / 1.875 = 8.
        // Mapped index = (0 + 8) % 12 = 8 (Sagittarius).
        $this->assertSame(Rashi::Dhanu, $d16Kundali->placements[Body::Moon->value]->rashi);
    }

    public function testShashtiamshaMapping(): void
    {
        $ascendant = new AscendantPosition(15.0, 15.0, Rashi::Mesha, 15.0); // 15 Aries
        
        $placements = [
            Body::Mars->value => new PlanetPlacement(
                body: Body::Mars,
                longitude: 76.0, // 16 degrees Gemini
                rashi: Rashi::Mithuna,
                house: 3
            ),
        ];
        
        $baseKundali = new Kundali($ascendant, $placements);

        $d60Kundali = $this->calculator->calculate($baseKundali, Varga::D60);

        // Mars is at 76 degrees. Rashi is 2 (Gemini).
        // 16 degrees in sign / 0.5 = 32.
        // Mapped index = (2 + 32) % 12 = 34 % 12 = 10 (Aquarius).
        $this->assertSame(Rashi::Kumbha, $d60Kundali->placements[Body::Mars->value]->rashi);
    }
}
