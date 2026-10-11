<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Accuracy;

use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Astronomy\Contracts\AstronomyEngineInterface;
use Vittix\Panchang\Astronomy\Engine\EngineFactory;
use Vittix\Panchang\Enum\AyanamshaSystem;

/**
 * Base class for the accuracy group.
 *
 * Builds the engine through the public factory (EngineFactory) with the same
 * configuration as Panchang::createDefault() — pure-PHP engine, Lahiri
 * ayanamsha — and provides fixture access.
 */
abstract class AccuracyTestCase extends TestCase
{
    private static ?AstronomyEngineInterface $engine = null;

    protected function repo(): FixtureRepository
    {
        return FixtureRepository::default();
    }

    protected function engine(): AstronomyEngineInterface
    {
        return self::$engine ??= EngineFactory::createPurePhpEngine(AyanamshaSystem::Lahiri);
    }

    /**
     * Skip the test cleanly when the fixture has not been generated yet.
     */
    protected function skipWithoutFixture(string $dataset): void
    {
        if ($this->repo()->has($dataset)) {
            return;
        }

        self::markTestSkipped(
            "Fixture '{$dataset}' is missing. Generate it with:\n"
            . "    pip install -r tools/reference/requirements-dev.txt\n"
            . '    python tools/reference/generate_fixtures.py'
        );
    }
}
