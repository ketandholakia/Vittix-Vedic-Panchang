<?php

/**
 * Developer utility: stamps tests/fixtures/oracle/daily_panchang_ahmedabad.json
 * with metadata and zeroes the raw-longitude placeholder fields.
 *
 * Usage: php tools/update_oracle.php [path-to-fixture.json]
 * (default: tests/fixtures/oracle/daily_panchang_ahmedabad.json relative
 * to the repository root; the historical hardcoded path from a previous
 * checkout location is kept as a comment below.)
 *
 * This is a repo-development tool, not part of the library API.
 */

// Historical hardcoded path (previous checkout location):
// $file = 'd:/ketan/github/vittixvedic/tests/fixtures/oracle/daily_panchang_ahmedabad.json';
$file = $argv[1] ?? __DIR__ . '/../tests/fixtures/oracle/daily_panchang_ahmedabad.json';
$raw = file_get_contents($file);
if ($raw === false) {
    fwrite(STDERR, "Cannot read fixture: $file\n");
    exit(1);
}
$data = json_decode($raw, true);
if (!is_array($data)) {
    fwrite(STDERR, "Invalid JSON in fixture: $file\n");
    exit(1);
}
$data['metadata'] = [
    'fixture_type' => 'independent_oracle',
    'location' => [
        'name' => 'Ahmedabad',
        'latitude' => 23.0225,
        'longitude' => 72.5714,
        'elevation_m' => 53
    ],
    'timezone' => 'Asia/Kolkata',
    'ayanamsha' => 'Lahiri',
    'month_system' => 'Amanta',
    'evaluation_rule' => 'local_sunrise',
    'sources' => [
        'astronomy' => 'Swiss Ephemeris swetest',
        'panchang' => 'manually verified reference'
    ],
    'verified_on' => '2026-07-31'
];
foreach($data['dates'] as $k => &$v) {
    $v['raw'] = [
        'sun_tropical_longitude' => 0,
        'moon_tropical_longitude' => 0,
        'ayanamsha' => 0,
        'sun_sidereal_longitude' => 0,
        'moon_sidereal_longitude' => 0,
        'elongation' => 0
    ];
}
file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "Updated\n";
