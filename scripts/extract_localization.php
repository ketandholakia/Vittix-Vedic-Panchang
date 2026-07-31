<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$enums = [
    \Vittix\Panchang\Enum\Tithi::class,
    \Vittix\Panchang\Enum\Nakshatra::class,
    \Vittix\Panchang\Enum\Yoga::class,
    \Vittix\Panchang\Enum\Karana::class,
    \Vittix\Panchang\Enum\Vara::class,
    \Vittix\Panchang\Enum\LunarMonth::class,
    \Vittix\Panchang\Enum\Paksha::class,
];

$langs = [
    'en' => 'nameEnglish',
    'gu' => 'nameGujarati',
    'hi' => 'nameHindi',
    'sa' => 'nameSanskrit',
];

$locDir = __DIR__ . '/../src/Localization';
if (!is_dir($locDir)) mkdir($locDir, 0755, true);

foreach ($langs as $langCode => $methodName) {
    $langDir = $locDir . '/' . $langCode;
    if (!is_dir($langDir)) mkdir($langDir, 0755, true);

    foreach ($enums as $enumClass) {
        $reflection = new ReflectionEnum($enumClass);
        $domain = strtolower($reflection->getShortName());
        
        $cases = $enumClass::cases();
        
        // Check if the enum has this language method
        if (!method_exists($cases[0], $methodName)) {
            continue;
        }

        $lines = ["<?php\n\nreturn ["];
        foreach ($cases as $case) {
            $name = $case->name;
            try {
                $translated = $case->$methodName();
                // Escape single quotes
                $translated = str_replace("'", "\\'", $translated);
                $lines[] = sprintf("    '%s' => '%s',", $name, $translated);
            } catch (\Throwable $e) {
                // Ignore if some reason method throws
            }
        }
        $lines[] = "];\n";
        
        file_put_contents($langDir . '/' . $domain . '.php', implode("\n", $lines));
        echo "Generated {$langCode}/{$domain}.php\n";
    }
}
