<?php
// Cross-test numbers: how each language does over every test.
declare(strict_types=1);

function median(array $values): ?float
{
    $values = array_values(array_filter($values, fn ($v) => $v !== null));
    if (!$values) {
        return null;
    }
    sort($values);
    $mid = intdiv(count($values), 2);
    return count($values) % 2 ? (float) $values[$mid] : ($values[$mid - 1] + $values[$mid]) / 2;
}

/**
 * One language across every test it appears in. The score is the geometric
 * mean of median / fastest median over its timed tests: 1.00 means fastest
 * everywhere. It is only comparable between languages timed on the same tests,
 * so `complete` says whether this language was timed on all of them.
 */
function language_summary(array $results, string $lang): array
{
    $rows = [];
    $logs = [];
    $wins = 0;
    foreach ($results['tests'] as $id => $t) {
        if (!in_array($lang, $t['langs'], true)) {
            continue;
        }
        $s = $t['times'][$lang] ?? null;
        $rank = $s ? array_search($lang, $t['ranked'], true) + 1 : null;
        $ratio = $s ? $s['median'] / $t['best'] : null;
        if ($ratio !== null) {
            $logs[] = log($ratio);
        }
        if ($rank === 1) {
            $wins++;
        }
        $rows[$id] = [
            'test'     => $t,
            'times'    => $s,
            'rank'     => $rank,
            'of'       => count($t['ranked']),
            'ratio'    => $ratio,
            'sizes'    => $t['sizes'][$lang] ?? [],
            'build_ms' => $t['build_ms'][$lang] ?? [],
        ];
    }
    $col = fn (string $key, string $profile) => array_map(fn ($r) => $r[$key][$profile] ?? null, $rows);
    return [
        'lang'     => $lang,
        'rows'     => $rows,
        'timed'    => count($logs),
        'complete' => $logs !== [] && count($logs) === $results['timed'],
        'score'    => $logs ? exp(array_sum($logs) / count($logs)) : null,
        'wins'     => $wins,
        'size'     => ['release' => median($col('sizes', 'release')), 'min' => median($col('sizes', 'min'))],
        'build'    => ['release' => median($col('build_ms', 'release')), 'min' => median($col('build_ms', 'min'))],
        'version'  => $results['toolchains'][$lang] ?? null,
    ];
}

/**
 * Every language's summary, best score first. Languages timed on every test
 * get a `place`; the rest follow with `place` null.
 * @return list<array>
 */
function language_ranking(array $results): array
{
    $all = array_map(fn ($l) => language_summary($results, $l), $results['langs']);
    $key = fn ($s) => [!$s['complete'], $s['score'] === null, $s['score'] ?? 0.0];
    usort($all, fn ($a, $b) => $key($a) <=> $key($b));
    $place = 0;
    foreach ($all as &$s) {
        $s['place'] = $s['complete'] ? ++$place : null;
    }
    unset($s);
    return $all;
}

/** @return array{bytes: float, lang: string, profile: string, test: string}|null */
function smallest_binary(array $tests, bool $largest = false): ?array
{
    $pick = null;
    foreach ($tests as $t) {
        foreach ($t['sizes'] as $lang => $profiles) {
            foreach ($profiles as $profile => $b) {
                if ($pick === null || ($largest ? $b > $pick['bytes'] : $b < $pick['bytes'])) {
                    $pick = ['bytes' => $b, 'lang' => $lang, 'profile' => $profile, 'test' => $t['name']];
                }
            }
        }
    }
    return $pick;
}

/** The test where slowest / fastest is largest. */
function widest_spread(array $tests): ?array
{
    $pick = null;
    foreach ($tests as $t) {
        if (count($t['ranked']) < 2) {
            continue;
        }
        $slow = end($t['ranked']);
        $ratio = $t['times'][$slow]['median'] / $t['best'];
        if ($pick === null || $ratio > $pick['ratio']) {
            $pick = ['ratio' => $ratio, 'test' => $t['name'], 'slow' => $slow, 'fast' => $t['ranked'][0]];
        }
    }
    return $pick;
}

/** Heatmap shade class h1..h6 for a slowdown ratio. */
function heat_class(float $ratio): string
{
    foreach (HEAT_STEPS as $i => $upper) {
        if ($ratio < $upper) {
            return 'h' . ($i + 1);
        }
    }
    return 'h' . (count(HEAT_STEPS) + 1);
}
