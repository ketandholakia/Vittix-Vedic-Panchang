<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Integration\Calendar;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
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
 * The Purnimanta month runs full moon to full moon and is named after the
 * Amanta lunation that follows its starting full moon
 * ({@see PurnimantaMonthCalculator::monthAt()}). These tests pin the
 * instant-level properties that must hold over random dates 1950–2100:
 *
 *   • Shukla Paksha (non-Kshaya lunation): Purnimanta == Amanta month,
 *     with the same Adhika flag.
 *   • Krishna Paksha (non-Kshaya lunation): Purnimanta == the NEXT Amanta
 *     month; for an Adhika lunation the next lunation carries the nija
 *     name of the same month, so Purnimanta == Amanta (not Adhika).
 *   • Kshaya lunations are excluded (their naming lunations skip a name).
 */
#[CoversClass(PurnimantaMonthCalculator::class)]
#[CoversClass(HinduCalendarCalculator::class)]
#[Group('slow')]
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
        $this->purnimanta = new PurnimantaMonthCalculator($this->lunarMonth);
        $this->timeFactory = new AstroTimeFactory();
    }

    public function testPurnimantaPropertiesOverRandomInstants(): void
    {
        mt_srand(20261011);
        $checked = 0;

        for ($i = 0; $i < 200; $i++) {
            $instant = $this->randomInstant();

            $amanta = $this->lunarMonth->amantaMonth($instant);
            $paksha = $this->tithi->calculate($instant)->paksha();

            // Classification of the Amanta lunation containing the instant.
            $lunation = $this->lunarMonth->amantaLunation(
                $this->lunarMonth->previousNewMoon($instant)
            );
            self::assertSame($amanta, $lunation->month, 'sanity: lunation month matches amantaMonth()');

            $purnimanta = $this->purnimanta->monthAt($instant);
            $context = sprintf(
                'at %s (amanta %d, paksha %s, adhika %s, kshaya %s)',
                $instant->utc->format('Y-m-d H:i:s'),
                $amanta->value,
                $paksha->value,
                $lunation->isAdhika ? 'yes' : 'no',
                $lunation->isKshaya ? 'yes' : 'no',
            );

            if ($lunation->isKshaya) {
                // Kshaya lunations skip a month name; the Purnimanta month is
                // still well-defined but does not follow the simple rules
                // below. Covered by PurnimantaAdhikaTest.
                continue;
            }

            if ($paksha === Paksha::Shukla) {
                self::assertSame($amanta, $purnimanta->month, "Shukla: Purnimanta == Amanta $context");
                self::assertSame($lunation->isAdhika, $purnimanta->isAdhika, "Shukla: Adhika flag follows the lunation $context");
            } elseif ($lunation->isAdhika) {
                // Krishna of an Adhika lunation: the naming lunation is the
                // following nija one, which re-uses the same month name.
                self::assertSame($amanta, $purnimanta->month, "Krishna of Adhika lunation: nija name $context");
                self::assertFalse($purnimanta->isAdhika, "Krishna of Adhika lunation: not Adhika $context");
            } else {
                // Krishna of a normal lunation: the month advances by one.
                // The Adhika flag comes from the naming lunation (the next
                // one), which may itself be Adhika — e.g. the Krishna
                // fortnight before Adhika Shravana 2023 IS Adhika Shravana.
                self::assertSame(
                    LunarMonth::from(($amanta->value % 12) + 1),
                    $purnimanta->month,
                    "Krishna: Purnimanta == Amanta+1 $context"
                );
                $nextLunation = $this->lunarMonth->amantaLunation(
                    $this->lunarMonth->nextNewMoon($this->lunarMonth->previousNewMoon($instant))
                );
                self::assertSame(
                    $nextLunation->isAdhika,
                    $purnimanta->isAdhika,
                    "Krishna: Adhika flag follows the naming lunation $context"
                );
            }
            $checked++;
        }

        self::assertGreaterThan(150, $checked, 'most random instants must fall in non-Kshaya lunations');
    }

    /**
     * The tithi's paksha must be exactly the elongation hemisphere:
     * Shukla ⟺ Moon−Sun elongation in [0°, 180°). Verified over 1000 random
     * instants — this is the property that links the tithi-based reasoning
     * above to the full-moon/new-moon based implementation.
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
