<?php

declare(strict_types=1);

/**
 * Panchang accuracy report CLI.
 *
 * Compares the engine against the Swiss Ephemeris reference fixtures and
 * prints a Markdown report; by default it also writes docs/accuracy-report.md.
 *
 * Usage:
 *   php tools/accuracy_report.php              # Markdown to stdout + docs/accuracy-report.md
 *   php tools/accuracy_report.php --json       # machine-readable summary to stdout
 *   php tools/accuracy_report.php --no-write   # Markdown to stdout only
 *   php tools/accuracy_report.php --out=PATH   # custom output path
 *   php tools/accuracy_report.php --fixtures=PATH
 *   php tools/accuracy_report.php --help
 *
 * See docs/accuracy.md (section "Validation") for how to (re)generate the
 * fixtures first.
 */

require __DIR__ . '/../vendor/autoload.php';

use Vittix\Panchang\Astronomy\Engine\EngineFactory;
use Vittix\Panchang\Enum\AyanamshaSystem;
use Vittix\Panchang\Tests\Accuracy\AccuracyReport;
use Vittix\Panchang\Tests\Accuracy\FixtureRepository;

const USAGE = <<<TXT
Usage:
  php tools/accuracy_report.php [options]

Options:
  --json           Print machine-readable JSON to stdout (no file write)
  --out=PATH       Markdown output path (default: docs/accuracy-report.md)
  --no-write       Print Markdown to stdout only, do not write the file
  --fixtures=PATH  Fixture directory (default: tests/fixtures/reference)
  --help           Show this help

TXT;

/**
 * @param array<string, mixed> $options
 */
function optionString(array $options, string $name): ?string
{
    $value = $options[$name] ?? null;

    return is_string($value) ? $value : null;
}

/**
 * @param array<string, mixed> $options
 */
function main(array $options): int
{
    if (isset($options['help'])) {
        echo USAGE;

        return 0;
    }

    $repo = FixtureRepository::default();
    $customFixtures = optionString($options, 'fixtures');
    if ($customFixtures !== null) {
        $repo = new FixtureRepository($customFixtures);
    }

    foreach (['body_longitudes', 'transitions', 'sun_moon_events'] as $dataset) {
        if (!$repo->has($dataset)) {
            file_put_contents(
                'php://stderr',
                "Missing fixture '{$dataset}' in {$repo->path($dataset)}\n"
                . "Generate it with:\n"
                . "    pip install -r tools/reference/requirements-dev.txt\n"
                . "    python tools/reference/generate_fixtures.py\n",
            );

            return 1;
        }
    }

    $engine = EngineFactory::createPurePhpEngine(AyanamshaSystem::Lahiri);
    $report = new AccuracyReport($repo, $engine);

    if (isset($options['json'])) {
        echo json_encode(
            $report->json(),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        ), "\n";

        return 0;
    }

    $markdown = $report->markdown();

    echo $markdown;

    if (!isset($options['no-write'])) {
        $outPath = optionString($options, 'out') ?? dirname(__DIR__) . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'accuracy-report.md';
        $outDir = dirname($outPath);
        if (!is_dir($outDir) && !mkdir($outDir, 0777, true) && !is_dir($outDir)) {
            file_put_contents('php://stderr', "Could not create output directory {$outDir}\n");

            return 1;
        }
        if (file_put_contents($outPath, $markdown) === false) {
            file_put_contents('php://stderr', "Could not write {$outPath}\n");

            return 1;
        }
        echo "\n(written to {$outPath})\n";
    }

    return 0;
}

$options = getopt('', ['json', 'out:', 'no-write', 'fixtures:', 'help']);
if (!is_array($options)) {
    $options = [];
}

exit(main($options));
