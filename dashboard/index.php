<?php
// Read-only dashboard for the results run.sh leaves in build/.
//   ./dashboard-start.sh   (runs php -S 127.0.0.1:8765 -t dashboard)
//   ./dashboard-stop.sh
declare(strict_types=1);

const LANG_NAMES = [
    'asm' => 'Assembly', 'c' => 'C', 'cpp' => 'C++', 'zig' => 'Zig',
    'rust' => 'Rust', 'swift' => 'Swift', 'go' => 'Go',
];

$root  = dirname(__DIR__);
$build = "$root/build";

// PHP defaults to UTC; show run times in the machine's zone.
if (preg_match('#zoneinfo/(.+)$#', (string) @readlink('/etc/localtime'), $m)) {
    date_default_timezone_set($m[1]);
}

function h(string|int|float $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES);
}

function lang_name(string $lang): string
{
    return LANG_NAMES[$lang] ?? $lang;
}

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
    foreach (scandir($root . ($rel === '' ? '' : "/$rel")) as $entry) {
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

function to_ms(float $v, string $unit): float
{
    return $v * match ($unit) {
        'ns' => 1e-6, 'us', 'µs' => 1e-3, 's' => 1e3, 'min' => 6e4, 'h' => 3.6e6, default => 1,
    };
}

function to_bytes(float $v, string $unit): float
{
    return $v * match ($unit) {
        'kB' => 1e3, 'MB' => 1e6, 'GB' => 1e9, 'KiB' => 1024, 'MiB' => 1048576, 'GiB' => 1073741824, default => 1,
    };
}

function fmt_size(float $b): string
{
    return $b >= 1048576 ? sprintf('%.2f MiB', $b / 1048576) : sprintf('%.1f KiB', $b / 1024);
}

function fmt_mem(float $b): string
{
    return $b >= 1048576 ? sprintf('%.1f MiB', $b / 1048576) : sprintf('%.0f KiB', $b / 1024);
}

function fmt_ms(float $ms): string
{
    return $ms >= 1000 ? sprintf('%.2f s', $ms / 1000) : sprintf('%.1f ms', $ms);
}

function lang_order(array $langs): array
{
    $known = array_keys(LANG_NAMES);
    usort($langs, fn ($a, $b) => [array_search($a, $known, true) === false, array_search($a, $known, true), $a]
        <=> [array_search($b, $known, true) === false, array_search($b, $known, true), $b]);
    return $langs;
}

function load_test(string $root, string $build, string $id): array
{
    $sizes = [];
    $libs = [];
    $buildMs = [];
    $hasBuildMs = false;
    foreach (read_csv("$build/$id/sizes.csv") as $r) {
        $sizes[$r['lang']][$r['profile']] = (float) $r['bytes'];
        $libs[$r['lang']] = $r['libs'];
        if (array_key_exists('build_ms', $r) && $r['build_ms'] !== '') {
            $hasBuildMs = true;
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
    $slash = strrpos($id, '/');
    return [
        'id'       => $id,
        'category' => $slash === false ? '' : substr($id, 0, $slash),
        'name'     => $slash === false ? $id : substr($id, $slash + 1),
        'args'     => is_file("$root/$id/args") ? trim((string) file_get_contents("$root/$id/args")) : '',
        'ranAt'    => is_file("$build/$id/sizes.csv") ? filemtime("$build/$id/sizes.csv") : null,
        'sizes'         => $sizes,
        'libs'          => $libs,
        'build_ms'      => $buildMs,
        'has_build_ms'  => $hasBuildMs,
        'times'         => $times,
        'langs'         => lang_order(array_keys($sizes)),
    ];
}

/**
 * Horizontal bar chart. Each row: label + one bar per series.
 * @param list<array{label: string, bars: list<array{series: int, value: float, text: string, tip: array}>}> $rows
 */
function bar_chart(array $rows, string $reserve = '10ch'): string
{
    $max = 0.0;
    foreach ($rows as $row) {
        foreach ($row['bars'] as $bar) {
            $max = max($max, $bar['value']);
        }
    }
    $out = '<div class="bars">';
    foreach ($rows as $row) {
        $out .= '<div class="bar-row"><div class="bar-label">' . h($row['label']) . '</div><div class="bar-stack">';
        foreach ($row['bars'] as $bar) {
            $f = $max > 0 ? $bar['value'] / $max : 0;
            $tip = json_encode($bar['tip'] + ['series' => $bar['series']], JSON_UNESCAPED_UNICODE);
            $out .= sprintf(
                '<div class="bar-track" tabindex="0" data-tip="%s" aria-label="%s" style="--f:%.4f;--reserve:%s">'
                . '<div class="bar s%d"></div><span class="bar-value">%s</span></div>',
                h($tip), h($bar['tip']['label'] . ': ' . $bar['tip']['value']), $f, h($reserve),
                $bar['series'], h($bar['text'])
            );
        }
        $out .= '</div></div>';
    }
    return $out . '</div>';
}

function size_chart(array $t): string
{
    $langs = $t['langs'];
    usort($langs, fn ($a, $b) => ($t['sizes'][$a]['release'] ?? 0) <=> ($t['sizes'][$b]['release'] ?? 0));
    $rows = [];
    foreach ($langs as $lang) {
        $bars = [];
        foreach (['release' => 1, 'min' => 2] as $profile => $series) {
            if (!isset($t['sizes'][$lang][$profile])) {
                continue;
            }
            $b = $t['sizes'][$lang][$profile];
            $bars[] = [
                'series' => $series, 'value' => $b, 'text' => fmt_size($b),
                'tip' => ['value' => fmt_size($b), 'label' => lang_name($lang) . " · $profile",
                    'rows' => [['Bytes', number_format($b)]]],
            ];
        }
        $rows[] = ['label' => lang_name($lang), 'bars' => $bars];
    }
    return bar_chart($rows);
}

function build_chart(array $t): string
{
    $langs = $t['langs'];
    usort($langs, fn ($a, $b) => ($t['build_ms'][$a]['release'] ?? 0) <=> ($t['build_ms'][$b]['release'] ?? 0));
    $rows = [];
    foreach ($langs as $lang) {
        $bars = [];
        foreach (['release' => 1, 'min' => 2] as $profile => $series) {
            if (!isset($t['build_ms'][$lang][$profile])) {
                continue;
            }
            $ms = $t['build_ms'][$lang][$profile];
            $bars[] = [
                'series' => $series, 'value' => $ms, 'text' => fmt_ms($ms),
                'tip' => ['value' => fmt_ms($ms), 'label' => lang_name($lang) . " · $profile",
                    'rows' => [['Milliseconds', number_format($ms)]]],
            ];
        }
        $rows[] = ['label' => lang_name($lang), 'bars' => $bars];
    }
    return bar_chart($rows);
}

function time_chart(array $t): string
{
    $times = $t['times'];
    uasort($times, fn ($a, $b) => $a['median'] <=> $b['median']);
    $best = reset($times)['median'];
    $rows = [];
    foreach ($times as $lang => $s) {
        $rel = sprintf('%.2f×', $s['median'] / $best);
        $rows[] = ['label' => lang_name($lang), 'bars' => [[
            'series' => 1, 'value' => $s['median'], 'text' => fmt_ms($s['median']) . " · $rel",
            'tip' => ['value' => fmt_ms($s['median']), 'label' => lang_name($lang) . ' · median', 'rows' => [
                ['vs fastest', $rel],
                ['Mean ± σ', fmt_ms($s['mean']) . ' ± ' . fmt_ms($s['stddev'])],
                ['Min … max', fmt_ms($s['min']) . ' … ' . fmt_ms($s['max'])],
            ]],
        ]]];
    }
    return bar_chart($rows, '15ch');
}

function memory_chart(array $t): string
{
    $times = array_filter($t['times'], fn ($s) => $s['rss'] !== null);
    uasort($times, fn ($a, $b) => $a['rss'] <=> $b['rss']);
    $rows = [];
    foreach ($times as $lang => $s) {
        $rows[] = ['label' => lang_name($lang), 'bars' => [[
            'series' => 1, 'value' => $s['rss'], 'text' => fmt_mem($s['rss']),
            'tip' => ['value' => fmt_mem($s['rss']), 'label' => lang_name($lang) . ' · peak RSS', 'rows' => []],
        ]]];
    }
    return bar_chart($rows);
}

/** @return list<array{label: string, value: string, note: string}> */
function kpis(array $t): array
{
    $out = [];
    $all = [];
    foreach ($t['sizes'] as $lang => $profiles) {
        foreach ($profiles as $profile => $b) {
            $all[] = [$b, $lang, $profile];
        }
    }
    if ($all) {
        sort($all);
        [$b, $lang, $profile] = $all[0];
        $out[] = ['label' => 'Smallest binary', 'value' => fmt_size($b), 'note' => lang_name($lang) . " · $profile"];
        [$b, $lang, $profile] = end($all);
        $out[] = ['label' => 'Largest binary', 'value' => fmt_size($b), 'note' => lang_name($lang) . " · $profile"];
    }
    if ($t['times']) {
        $times = $t['times'];
        uasort($times, fn ($a, $b) => $a['median'] <=> $b['median']);
        $fast = array_key_first($times);
        $slow = array_key_last($times);
        $out[] = ['label' => 'Fastest', 'value' => fmt_ms($times[$fast]['median']), 'note' => lang_name($fast) . ' · median'];
        $out[] = ['label' => 'Slowest', 'value' => fmt_ms($times[$slow]['median']),
            'note' => lang_name($slow) . sprintf(' · %.2f× the fastest', $times[$slow]['median'] / $times[$fast]['median'])];
    }
    return $out;
}

$tests = array_map(fn ($id) => load_test($root, $build, $id), find_tests($root));
$toolchains = read_csv("$build/toolchains.csv");
$lastRun = max([0, ...array_map(fn ($t) => $t['ranAt'] ?? 0, $tests)]);
$langCount = count(array_unique(array_merge([], ...array_map(fn ($t) => $t['langs'], $tests))));
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>language-benches</title>
<style>
:root {
  color-scheme: light;
  --page: #f9f9f7;
  --surface: #fcfcfb;
  --ink: #0b0b0b;
  --ink-2: #52514e;
  --muted: #898781;
  --grid: #e1e0d9;
  --axis: #c3c2b7;
  --border: rgba(11, 11, 11, 0.10);
  --s1: #2a78d6;
  --s2: #eb6834;
  --focus: #2a78d6;
}
@media (prefers-color-scheme: dark) {
  :root:not([data-theme="light"]) {
    color-scheme: dark;
    --page: #0d0d0d;
    --surface: #1a1a19;
    --ink: #f0efec;
    --ink-2: #c3c2b7;
    --muted: #898781;
    --grid: #2c2c2a;
    --axis: #383835;
    --border: rgba(255, 255, 255, 0.10);
    --s1: #3987e5;
    --s2: #d95926;
    --focus: #3987e5;
  }
}
:root[data-theme="dark"] {
  color-scheme: dark;
  --page: #0d0d0d;
  --surface: #1a1a19;
  --ink: #f0efec;
  --ink-2: #c3c2b7;
  --muted: #898781;
  --grid: #2c2c2a;
  --axis: #383835;
  --border: rgba(255, 255, 255, 0.10);
  --s1: #3987e5;
  --s2: #d95926;
  --focus: #3987e5;
}

* { box-sizing: border-box; }
body {
  margin: 0;
  background: var(--page);
  color: var(--ink);
  font: 14px/1.45 system-ui, -apple-system, "Segoe UI", sans-serif;
}
main { max-width: 1120px; margin: 0 auto; padding: 32px 16px 64px; }
h1, h2, h3, p { margin: 0; }
code { font: 12px ui-monospace, "SF Mono", Menlo, monospace; }

.page-head h1 { font-size: 22px; font-weight: 600; letter-spacing: -0.01em; }
.page-head p { color: var(--ink-2); margin-top: 4px; }

.test-nav { display: flex; flex-wrap: wrap; gap: 8px; margin: 20px 0 8px; }
.test-nav a {
  color: var(--ink-2); text-decoration: none; font-size: 13px;
  padding: 4px 10px; border: 1px solid var(--border); border-radius: 999px; background: var(--surface);
}
.test-nav a:hover { color: var(--ink); }
.test-nav a:focus-visible { outline: 2px solid var(--focus); outline-offset: 2px; }

.test { margin-top: 36px; scroll-margin-top: 16px; }
.test-head { display: flex; flex-wrap: wrap; align-items: baseline; justify-content: space-between; gap: 4px 16px; }
.eyebrow { color: var(--muted); font-size: 12px; text-transform: uppercase; letter-spacing: 0.06em; }
.test-head h2 { font-size: 18px; font-weight: 600; }
.test-meta { color: var(--ink-2); font-size: 13px; }

.kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-top: 14px; }
.kpi, .card { background: var(--surface); border: 1px solid var(--border); border-radius: 10px; }
.kpi { padding: 12px 14px; }
.kpi-label { color: var(--ink-2); font-size: 13px; }
.kpi-value { font-size: 24px; font-weight: 600; margin-top: 2px; }
.kpi-note { color: var(--muted); font-size: 12px; }

.charts { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 12px; margin-top: 12px; }
.card { margin: 0; padding: 14px 16px 16px; min-width: 0; }
.card figcaption h3 { font-size: 14px; font-weight: 600; }
.card figcaption p { color: var(--ink-2); font-size: 12px; margin-top: 2px; }

.legend { display: flex; gap: 14px; margin-top: 10px; color: var(--ink-2); font-size: 12px; }
.legend span { display: inline-flex; align-items: center; gap: 6px; }
.legend i { width: 10px; height: 10px; border-radius: 2px; display: inline-block; }

.bars { margin-top: 14px; display: grid; gap: 10px; }
.bar-row { display: grid; grid-template-columns: 72px minmax(0, 1fr); align-items: center; }
.bar-label { color: var(--ink-2); font-size: 13px; }
.bar-stack { display: grid; gap: 2px; border-left: 1px solid var(--axis); }
.bar-track { display: flex; align-items: center; gap: 6px; height: 18px; border-radius: 3px; outline: none; }
.bar-track:focus-visible { box-shadow: 0 0 0 2px var(--focus); }
.bar {
  flex: none; height: 14px; border-radius: 0 4px 4px 0;
  width: max(2px, calc((100% - var(--reserve)) * var(--f)));
  transition: filter 120ms;
}
.bar-stack:has(.bar-track + .bar-track) .bar { height: 12px; }
.bar.s1 { background: var(--s1); }
.bar.s2 { background: var(--s2); }
.bar-track:hover .bar, .bar-track:focus-visible .bar { filter: brightness(1.15); }
.bar-value { color: var(--ink-2); font-size: 12px; white-space: nowrap; font-variant-numeric: tabular-nums; }

.table-view { margin-top: 12px; }
.table-view summary { cursor: pointer; color: var(--ink-2); font-size: 13px; width: fit-content; }
.table-view summary:focus-visible { outline: 2px solid var(--focus); outline-offset: 2px; }
.table-wrap { overflow-x: auto; margin-top: 8px; background: var(--surface); border: 1px solid var(--border); border-radius: 10px; }
table { border-collapse: collapse; width: 100%; font-size: 13px; }
th, td { padding: 8px 12px; text-align: left; white-space: nowrap; border-bottom: 1px solid var(--grid); }
tr:last-child td { border-bottom: 0; }
th { color: var(--muted); font-weight: 500; font-size: 12px; }
td.num, th.num { text-align: right; font-variant-numeric: tabular-nums; }
td.libs { color: var(--ink-2); }

.empty { margin-top: 12px; padding: 16px; color: var(--ink-2); background: var(--surface); border: 1px solid var(--border); border-radius: 10px; }

.tip {
  position: fixed; z-index: 10; pointer-events: none; min-width: 150px; max-width: 260px;
  padding: 8px 10px; border-radius: 8px; font-size: 12px;
  background: var(--surface); color: var(--ink); border: 1px solid var(--border);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
}
.tip strong { display: block; font-size: 14px; font-weight: 600; }
.tip .tip-label { display: flex; align-items: center; gap: 6px; color: var(--ink-2); }
.tip .tip-key { width: 10px; height: 2px; border-radius: 1px; }
.tip .tip-row { display: flex; justify-content: space-between; gap: 12px; margin-top: 4px; color: var(--ink-2); }
.tip .tip-row span:last-child { color: var(--ink); font-variant-numeric: tabular-nums; }

footer { margin-top: 48px; }
footer h2 { font-size: 14px; font-weight: 600; }
footer p { color: var(--ink-2); font-size: 12px; margin-top: 2px; }
footer .table-wrap { margin-top: 10px; }
footer td { white-space: normal; }
footer td:first-child { white-space: nowrap; width: 1%; }
</style>
</head>
<body>
<main>
  <header class="page-head">
    <h1>language-benches</h1>
    <p>
      The same programs in <?= $langCount ?: 'several' ?> compiled languages · macOS arm64
      <?php if ($lastRun): ?> · last run <?= h(date('j M Y, H:i', $lastRun)) ?><?php endif ?>
    </p>
  </header>

<?php if (!$tests): ?>
  <p class="empty">No tests found. Add a <code>&lt;category&gt;/&lt;test&gt;/expected.txt</code>.</p>
<?php else: ?>
  <nav class="test-nav" aria-label="Tests">
<?php foreach ($tests as $t): ?>
    <a href="#<?= h($t['id']) ?>"><?= h($t['id']) ?></a>
<?php endforeach ?>
  </nav>

<?php foreach ($tests as $t): ?>
  <section class="test" id="<?= h($t['id']) ?>">
    <header class="test-head">
      <div>
        <p class="eyebrow"><?= h($t['category']) ?></p>
        <h2><?= h($t['name']) ?></h2>
      </div>
      <p class="test-meta">
        <?php if ($t['args'] !== ''): ?>args <code><?= h($t['args']) ?></code> · <?php endif ?>
        <?= $t['ranAt'] ? 'run ' . h(date('j M Y, H:i', $t['ranAt'])) : 'not run yet' ?>
      </p>
    </header>

<?php if (!$t['sizes']): ?>
    <p class="empty">No results yet. Run <code>./run.sh <?= h($t['id']) ?></code>, then reload.</p>
<?php else: ?>
    <div class="kpis">
<?php foreach (kpis($t) as $k): ?>
      <div class="kpi">
        <p class="kpi-label"><?= h($k['label']) ?></p>
        <p class="kpi-value"><?= h($k['value']) ?></p>
        <p class="kpi-note"><?= h($k['note']) ?></p>
      </div>
<?php endforeach ?>
    </div>

    <div class="charts">
      <figure class="card">
        <figcaption>
          <h3>Binary size</h3>
          <p>Bytes on disk. Smaller is better.</p>
        </figcaption>
        <div class="legend" aria-hidden="true">
          <span><i style="background: var(--s1)"></i>release</span>
          <span><i style="background: var(--s2)"></i>min (size-optimized, stripped)</span>
        </div>
        <?= size_chart($t) ?>
      </figure>
<?php if ($t['has_build_ms']): ?>
      <figure class="card">
        <figcaption>
          <h3>Build time</h3>
          <p>Wall-clock time to compile. Lower is better.</p>
        </figcaption>
        <div class="legend" aria-hidden="true">
          <span><i style="background: var(--s1)"></i>release</span>
          <span><i style="background: var(--s2)"></i>min (size-optimized, stripped)</span>
        </div>
        <?= build_chart($t) ?>
      </figure>
<?php endif ?>
<?php if ($t['times']): ?>
      <figure class="card">
        <figcaption>
          <h3>Run time</h3>
          <p>Median wall-clock time of the release build. Lower is better.</p>
        </figcaption>
        <?= time_chart($t) ?>
      </figure>
<?php if (array_filter($t['times'], fn ($s) => $s['rss'] !== null)): ?>
      <figure class="card">
        <figcaption>
          <h3>Peak memory</h3>
          <p>Peak resident set size of the release build. Lower is better.</p>
        </figcaption>
        <?= memory_chart($t) ?>
      </figure>
<?php endif ?>
<?php endif ?>
    </div>

    <details class="table-view">
      <summary>Table view</summary>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Language</th>
              <th class="num">Release size</th>
              <th class="num">Min size</th>
<?php if ($t['has_build_ms']): ?>
              <th class="num">Release build</th>
              <th class="num">Min build</th>
<?php endif ?>
<?php if ($t['times']): ?>
              <th class="num">Median</th>
              <th class="num">Mean ± σ</th>
              <th class="num">Min</th>
              <th class="num">Max</th>
              <th class="num">Peak RSS</th>
<?php endif ?>
              <th>Linked libraries</th>
            </tr>
          </thead>
          <tbody>
<?php foreach ($t['langs'] as $lang): $s = $t['times'][$lang] ?? null; ?>
            <tr>
              <td><?= h(lang_name($lang)) ?></td>
              <td class="num"><?= isset($t['sizes'][$lang]['release']) ? h(fmt_size($t['sizes'][$lang]['release'])) : '–' ?></td>
              <td class="num"><?= isset($t['sizes'][$lang]['min']) ? h(fmt_size($t['sizes'][$lang]['min'])) : '–' ?></td>
<?php if ($t['has_build_ms']): ?>
              <td class="num"><?= isset($t['build_ms'][$lang]['release']) ? h(fmt_ms($t['build_ms'][$lang]['release'])) : '–' ?></td>
              <td class="num"><?= isset($t['build_ms'][$lang]['min']) ? h(fmt_ms($t['build_ms'][$lang]['min'])) : '–' ?></td>
<?php endif ?>
<?php if ($t['times']): ?>
              <td class="num"><?= $s ? h(fmt_ms($s['median'])) : '–' ?></td>
              <td class="num"><?= $s ? h(fmt_ms($s['mean']) . ' ± ' . fmt_ms($s['stddev'])) : '–' ?></td>
              <td class="num"><?= $s ? h(fmt_ms($s['min'])) : '–' ?></td>
              <td class="num"><?= $s ? h(fmt_ms($s['max'])) : '–' ?></td>
              <td class="num"><?= $s && $s['rss'] !== null ? h(fmt_mem($s['rss'])) : '–' ?></td>
<?php endif ?>
              <td class="libs"><?= h(str_replace(' ', ', ', $t['libs'][$lang] ?? '')) ?></td>
            </tr>
<?php endforeach ?>
          </tbody>
        </table>
      </div>
    </details>
<?php endif ?>
  </section>
<?php endforeach ?>
<?php endif ?>

<?php if ($toolchains): ?>
  <footer>
    <h2>Toolchains</h2>
    <p>Recorded by the last <code>./run.sh</code>.</p>
    <div class="table-wrap">
      <table>
        <tbody>
<?php foreach ($toolchains as $tc): ?>
          <tr><td><?= h(lang_name($tc['lang'])) ?></td><td><code><?= h($tc['version']) ?></code></td></tr>
<?php endforeach ?>
        </tbody>
      </table>
    </div>
  </footer>
<?php endif ?>
</main>

<div class="tip" id="tip" role="tooltip" hidden></div>
<script>
(() => {
  const tip = document.getElementById('tip');

  function fill(track) {
    const d = JSON.parse(track.dataset.tip);
    const value = document.createElement('strong');
    value.textContent = d.value;
    const label = document.createElement('div');
    label.className = 'tip-label';
    const key = document.createElement('i');
    key.className = 'tip-key';
    key.style.background = `var(--s${d.series})`;
    label.append(key, document.createTextNode(d.label));
    tip.replaceChildren(value, label);
    for (const [k, v] of d.rows || []) {
      const row = document.createElement('div');
      row.className = 'tip-row';
      const a = document.createElement('span');
      a.textContent = k;
      const b = document.createElement('span');
      b.textContent = v;
      row.append(a, b);
      tip.append(row);
    }
    tip.hidden = false;
  }

  function place(x, y) {
    const r = tip.getBoundingClientRect();
    const left = Math.min(x + 14, innerWidth - r.width - 8);
    const top = y + 14 + r.height > innerHeight ? y - r.height - 10 : y + 14;
    tip.style.left = `${Math.max(8, left)}px`;
    tip.style.top = `${Math.max(8, top)}px`;
  }

  for (const track of document.querySelectorAll('.bar-track')) {
    track.addEventListener('pointerenter', () => fill(track));
    track.addEventListener('pointermove', (e) => place(e.clientX, e.clientY));
    track.addEventListener('pointerleave', () => { tip.hidden = true; });
    track.addEventListener('focus', () => {
      fill(track);
      const r = track.getBoundingClientRect();
      place(r.left + 40, r.bottom - 8);
    });
    track.addEventListener('blur', () => { tip.hidden = true; });
  }
})();
</script>
</body>
</html>
