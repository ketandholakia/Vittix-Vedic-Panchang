<?php

declare(strict_types=1);

namespace Vittix\Panchang\Astronomy\Engine;

use Vittix\Panchang\Astronomy\Contracts\AstronomyEngineInterface;
use Vittix\Panchang\Astronomy\Sidereal\KrishnamurtiAyanamsha;
use Vittix\Panchang\Astronomy\Sidereal\LahiriAyanamsha;
use Vittix\Panchang\Astronomy\Sidereal\RamanAyanamsha;
use Vittix\Panchang\Enum\AyanamshaSystem;
use Psr\SimpleCache\CacheInterface;

final class EngineFactory
{
    /**
     * Create the default pure-PHP astronomy engine with the specified Ayanamsha system.
     */
    public static function createPurePhpEngine(
        AyanamshaSystem $ayanamshaSystem = AyanamshaSystem::Lahiri,
        ?CacheInterface $cache = null
    ): AstronomyEngineInterface {
        $ayanamshaCalc = match ($ayanamshaSystem) {
            AyanamshaSystem::Lahiri => new LahiriAyanamsha(),
            AyanamshaSystem::Raman => new RamanAyanamsha(),
            AyanamshaSystem::Krishnamurti => new KrishnamurtiAyanamsha(),
        };

        return new PurePhpAstronomyEngine($ayanamshaCalc, $cache);
    }
}
