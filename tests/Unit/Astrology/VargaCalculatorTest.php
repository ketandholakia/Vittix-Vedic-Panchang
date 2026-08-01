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
}
