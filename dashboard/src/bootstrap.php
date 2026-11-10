<?php
// Loads the dashboard: configuration, then each module. Included by
// public/index.php; nothing here produces output.
declare(strict_types=1);

/** Display names, in the order languages appear everywhere. Add new languages here. */
const LANG_NAMES = [
    'asm' => 'Assembly', 'c' => 'C', 'cpp' => 'C++', 'zig' => 'Zig',
    'rust' => 'Rust', 'swift' => 'Swift', 'go' => 'Go',
];

/** Build profiles, as lib/langs.sh defines them. */
const PROFILES = [
    'release' => 'The usual optimized build for that toolchain.',
    'min'     => 'Size-optimized, with dead code and symbols stripped.',
];

/** Upper bounds (slowdown vs the fastest) of heatmap shades h1..h5; h6 is anything slower. */
const HEAT_STEPS = [1.1, 1.25, 1.5, 2.0, 3.0];

define('DASHBOARD_DIR', dirname(__DIR__));
define('BENCH_ROOT', dirname(DASHBOARD_DIR));

// PHP defaults to UTC; show run times in the machine's zone.
if (preg_match('#zoneinfo/(.+)$#', (string) @readlink('/etc/localtime'), $m)) {
    date_default_timezone_set($m[1]);
}

require __DIR__ . '/format.php';
require __DIR__ . '/data.php';
require __DIR__ . '/stats.php';
require __DIR__ . '/charts.php';
require __DIR__ . '/view.php';
require __DIR__ . '/pages.php';
require __DIR__ . '/app.php';
