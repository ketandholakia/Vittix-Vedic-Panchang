<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Integration\Calendar;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Astronomy\Engine\EngineFactory;
use Vittix\Panchang\Calendar\HinduCalendarCalculator;
use Vittix\Panchang\Calendar\LunarMonthCalculator;
use Vittix\Panchang\Calendar\PurnimantaMonthCalculator;
use Vittix\Panchang\Enum\LunarMonth;
use Vittix\Panchang\Enum\Paksha;
use Vittix\Panchang\Math\Angle;
use Vittix\Panchang\Panchanga\TithiCalculator;
use Vittix\Panchang\Time\AstroTimeFactory;

/**
 * Property tests for the Purnimanta month derivation.
 *
 * Historically the month-shift rule lived in two places:
 * PurnimantaMonthCalculator (used by PanchangaCalculator forDay, keyed off
 * the tithi's paksha) and HinduCalendarCalculator::derivePurnimantaMonth
 * (keyed off previous-full-moon vs previous-new-moon order). The shift rule
 * is now single-sourced in PurnimantaMonthCalculator; these tests pin the
 * seam so the two paksha-detection strategies cannot silently diverge.
 */
#[CoversClass(PurnimantaMonthCalculator::class)]
#[CoversClass(HinduCalendarCalculator::class)]
final class PurnimantaAgreementPropertyTest extends TestCase
{
    private LunarMonthCalculator $lunarMonth;

    private TithiCalculator $tithi;

    private PurnimantaMonthCalculator $purnimanta;

    private AstroTimeFactory $timeFactory;

    protected function setUp(): void
    {
        $engine = EngineFactory::createPurePhpEngine();
        $this->lunarMonth = new LunarMonthCalculator($engine);
        $this->tithi = new TithiCalculator($engine);
        $this->purnimanta = new PurnimantaMonthCalculator();
        $this->timeFactory = new AstroTimeFactory();
    }

    /**
     * The month-shift rule has only 24 inputs — verify all of them, which is
     * stronger than any random sample.
     */
    public function testShiftFormulaExhaustively(): void
    {
        foreach (LunarMonth::cases() as $month) {
            self::assertSame(
                $month,
                $this->purnimanta->purnimantaMonth($month, Paksha::Shukla),
                "Shukla Paksha must keep the Amanta month (month {$month->value})"
            );

            $expected = LunarMonth::from(($month->value % 12) + 1);
            self::assertSame(
                $expected,
                $this->purnimanta->purnimantaMonth($month, Paksha::Krishna),
                "Krishna Paksha must shift to the next month (month {$month->value})"
            );
        }
    }

    /**
     * End-to-end agreement on real dates: the tithi-based paksha detection
     * (PanchangaCalculator path) and the phase-order detection
     * (HinduCalendarCalculator path) must pick the same Purnimanta month.
     *
     * A razor-edge disagreement is only possible within the phase-search
     * tolerance of an exact new/full moon moment; any failure here is a
     * genuine seam bug, not test noise.
     */
    public function testPakshaDetectionStrategiesAgreeOnRealDates(): void
    {
        mt_srand(20261011);
        $checked = 0;

        for ($i = 0; $i < 300; $i++) {
            $instant = $this->randomInstant();

            $amanta = $this->lunarMonth->amantaMonth($instant);
            $paksha = $this->tithi->calculate($instant)->paksha();

            $tithiBased = $this->purnimanta->purnimantaMonth($amanta, $paksha);

            // The historical HinduCalendarCalculator logic, inline:
            $prevFm = $this->lunarMonth->previousFullMoon($instant);
            $prevNm = $this->lunarMonth->previousNewMoon($instant);
            $inKrishnaPaksha = $prevFm->julianDayUt > $prevNm->julianDayUt;
            $phaseBased = $inKrishnaPaksha
                ? LunarMonth::from(($amanta->value % 12) + 1)
                : $amanta;

            self::assertSame(
                $tithiBased,
                $phaseBased,
                sprintf(
                    'Purnimanta month must agree at %s (amanta %d, tithi paksha %s)',
                    $instant->utc->format('Y-m-d H:i:s'),
                    $amanta->value,
                    $paksha->value
                )
            );
            $checked++;
        }

        self::assertSame(300, $checked);
    }

    /**
     * The tithi's paksha must be exactly the elongation hemisphere:
     * Shukla ⟺ Moon−Sun elongation in [0°, 180°). Verified over 1000 random
     * instants — this is the property that makes both detection strategies
     * equivalent away from phase-boundary instants.
     */
    public function testTithiPakshaMatchesElongationHemisphereOver1000Instants(): void
    {
        mt_srand(19502100);
        $engine = EngineFactory::createPurePhpEngine();

        for ($i = 0; $i < 1000; $i++) {
            $instant = $this->randomInstant();

            $moon = $engine->tropicalLongitude(\Vittix\Panchang\Enum\Body::Moon, $instant);
            $sun = $engine->tropicalLongitude(\Vittix\Panchang\Enum\Body::Sun, $instant);
            $elongation = Angle::normalize($moon - $sun);

            $expectedPaksha = $elongation < 180.0 ? Paksha::Shukla : Paksha::Krishna;

            self::assertSame(
                $expectedPaksha,
                $this->tithi->calculate($instant)->paksha(),
                sprintf(
                    'paksha must match the elongation hemisphere at %s (elongation %.6f)',
                    $instant->utc->format('Y-m-d H:i:s'),
                    $elongation
                )
            );
        }
    }

    private function randomInstant(): \Vittix\Panchang\ValueObject\AstroInstant
    {
        $date = sprintf(
            '%04d-%02d-%02dT%02d:%02d:%02dZ',
            mt_rand(1950, 2100),
            mt_rand(1, 12),
            mt_rand(1, 28),
            mt_rand(0, 23),
            mt_rand(0, 59),
            mt_rand(0, 59)
        );

        return $this->timeFactory->fromDateTime(new DateTimeImmutable($date));
    }
}
