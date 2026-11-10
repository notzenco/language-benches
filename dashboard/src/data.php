<?php
// Reads what run.sh leaves in build/. Read-only.
declare(strict_types=1);

/** @return list<array<string, string>> rows keyed by header */
function read_csv(string $path): array
{
    if (!is_file($path) || !($fh = fopen($path, 'r'))) {
        return [];
    }
    $header = fgetcsv($fh, null, ',', '"', '') ?: [];
    $rows = [];
    while (($r = fgetcsv($fh, null, ',', '"', '')) !== false) {
        if (count($r) === count($header)) {
            $rows[] = array_combine($header, $r);
        }
    }
    fclose($fh);
    return $rows;
}

/** Test directories (holding expected.txt) relative to $root, like run.sh finds them. */
function find_tests(string $root, string $rel = ''): array
{
    $found = [];
    foreach (scandir($root . ($rel === '' ? '' : "/$rel")) ?: [] as $entry) {
        $path = $rel === '' ? $entry : "$rel/$entry";
        if ($entry[0] === '.' || in_array($path, ['build', 'dashboard', 'lib'], true) || !is_dir("$root/$path")) {
            continue;
        }
        if (is_file("$root/$path/expected.txt")) {
            $found[] = $path;
        } else {
            array_push($found, ...find_tests($root, $path));
        }
    }
    sort($found);
    return $found;
}

function load_test(string $root, string $build, string $id): array
{
    $sizes = [];
    $libs = [];
    $buildMs = [];
    foreach (read_csv("$build/$id/sizes.csv") as $r) {
        $sizes[$r['lang']][$r['profile']] = (float) $r['bytes'];
        $libs[$r['lang']] = $r['libs'];
        if (($r['build_ms'] ?? '') !== '') {
            $buildMs[$r['lang']][$r['profile']] = (float) $r['build_ms'];
        }
    }
    $times = [];
    foreach (read_csv("$build/$id/times.csv") as $r) {
        $tu = $r['time_wall_clock_unit'];
        $mu = $r['memory_peak_resident_unit'] ?? 'B';
        $times[$r['command']] = [
            'median' => to_ms((float) $r['time_wall_clock_median'], $tu),
            'mean'   => to_ms((float) $r['time_wall_clock_mean'], $tu),
            'stddev' => to_ms((float) $r['time_wall_clock_stddev'], $tu),
            'min'    => to_ms((float) $r['time_wall_clock_min'], $tu),
            'max'    => to_ms((float) $r['time_wall_clock_max'], $tu),
            'rss'    => isset($r['memory_peak_resident_median'])
                ? to_bytes((float) $r['memory_peak_resident_median'], $mu) : null,
        ];
    }
    uasort($times, fn ($a, $b) => $a['median'] <=> $b['median']);

    $slash = strrpos($id, '/');
    return [
        'id'       => $id,
        'category' => $slash === false ? '' : substr($id, 0, $slash),
        'name'     => $slash === false ? $id : substr($id, $slash + 1),
        'args'     => is_file("$root/$id/args") ? trim((string) file_get_contents("$root/$id/args")) : '',
        'ranAt'    => is_file("$build/$id/sizes.csv") ? filemtime("$build/$id/sizes.csv") : null,
        'sizes'    => $sizes,
        'libs'     => $libs,
        'build_ms' => $buildMs,
        'times'    => $times,                   // fastest first
        'ranked'   => array_keys($times),        // languages, fastest first
        'best'     => $times ? reset($times)['median'] : null,
        'langs'    => lang_order(array_keys($sizes + $times)),
    ];
}

/** Everything the pages need, read once per request. */
function load_results(string $root): array
{
    $build = "$root/build";
    $tests = [];
    foreach (find_tests($root) as $id) {
        $tests[$id] = load_test($root, $build, $id);
    }
    $toolchains = [];
    foreach (read_csv("$build/toolchains.csv") as $r) {
        $toolchains[$r['lang']] = $r['version'];
    }
    $langs = [];
    foreach ($tests as $t) {
        $langs += array_flip($t['langs']);
    }
    return [
        'tests'      => $tests,
        'toolchains' => $toolchains,
        'langs'      => lang_order(array_keys($langs)),
        'timed'      => count(array_filter($tests, fn ($t) => $t['times'] !== [])),
        'lastRun'    => max([0, ...array_map(fn ($t) => $t['ranAt'] ?? 0, $tests)]),
    ];
}
