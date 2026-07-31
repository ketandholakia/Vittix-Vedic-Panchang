<?php

declare(strict_types=1);

namespace Vittix\Panchang\Localization;

use UnitEnum;

/**
 * Translator that loads translations from PHP array files.
 *
 * Files are expected to be in the structure: {lang}/{domain}.php
 * For example: hi/tithi.php, gu/nakshatra.php
 */
final class ArrayTranslator implements TranslatorInterface
{
    /**
     * @var array<string, array<string, string>> Cached translation arrays
     */
    private array $cache = [];

    public function __construct(
        private readonly string $basePath = __DIR__
    ) {
    }

    public function translate(UnitEnum $enum, string $lang = 'en'): string
    {
        // Extract domain from Enum class name (e.g., 'Vittix\Panchang\Enum\Tithi' -> 'tithi')
        $reflection = new \ReflectionClass($enum);
        $domain = strtolower($reflection->getShortName());
        
        // Load translations if not already cached
        $cacheKey = "{$lang}/{$domain}";
        if (!isset($this->cache[$cacheKey])) {
            $this->cache[$cacheKey] = $this->loadTranslations($lang, $domain);
        }

        $translations = $this->cache[$cacheKey];
        $enumName = $enum->name;

        // Return translation if exists
        if (isset($translations[$enumName])) {
            return $translations[$enumName];
        }

        // Fallback to 'en' if requested lang is missing a key
        if ($lang !== 'en') {
            return $this->translate($enum, 'en');
        }

        // Ultimate fallback: return the enum name itself
        return $enumName;
    }

    /**
     * @return array<string, string>
     */
    private function loadTranslations(string $lang, string $domain): array
    {
        $file = sprintf('%s/%s/%s.php', rtrim($this->basePath, '/'), $lang, $domain);

        if (!file_exists($file)) {
            return [];
        }

        $data = include $file;

        return is_array($data) ? $data : [];
    }
}
