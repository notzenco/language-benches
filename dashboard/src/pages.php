<?php
// One function per page: works out what the page shows and hands it to a
// template in views/pages/. Each returns
//   title   <title> and h1
//   view    template name under views/pages/
//   nav     sidebar href to mark as current
//   vars    template variables
//   status  HTTP status (default 200)
declare(strict_types=1);

function page_overview(array $r): array
{
    $ranking = language_ranking($r);
    $ranked = array_values(array_filter($ranking, fn ($s) => $s['place'] !== null));
    $partial = array_values(array_filter($ranking, fn ($s) => $s['place'] === null && $s['timed'] > 0));

    $stats = [];
    if ($ranked) {
        $stats[] = ['Best overall', lang_name($ranked[0]['lang']), fmt_ratio($ranked[0]['score']) . ' the fastest, geometric mean'];
    }
    $byWins = $ranking;
    usort($byWins, fn ($a, $b) => $b['wins'] <=> $a['wins']);
    if ($byWins && $byWins[0]['wins'] > 0) {
        $stats[] = ['Most wins', lang_name($byWins[0]['lang']), "fastest in {$byWins[0]['wins']} of {$r['timed']} tests"];
    }
    if ($small = smallest_binary($r['tests'])) {
        $stats[] = ['Smallest binary', fmt_size($small['bytes']),
            lang_name($small['lang']) . " · {$small['test']} · {$small['profile']}"];
    }
    if ($spread = widest_spread($r['tests'])) {
        $stats[] = ['Widest spread', fmt_ratio($spread['ratio']),
            "{$spread['test']} · " . lang_name($spread['slow']) . ' vs ' . lang_name($spread['fast'])];
    }

    // Heatmap: every test, every language, shaded by slowdown vs that test's fastest.
    $heat = [];
    foreach ($r['tests'] as $id => $t) {
        $cells = [];
        foreach ($r['langs'] as $lang) {
            $s = $t['times'][$lang] ?? null;
            if ($s === null) {
                $cells[$lang] = null;
                continue;
            }
            $ratio = $s['median'] / $t['best'];
            $rank = array_search($lang, $t['ranked'], true) + 1;
            $cells[$lang] = [
                'text'  => fmt_ms_bare($s['median']),
                'class' => heat_class($ratio),
                'best'  => $rank === 1,
                'tip'   => ['value' => fmt_ms($s['median']), 'label' => lang_name($lang) . " · {$t['name']}",
                    'color' => 'var(--heat-' . substr(heat_class($ratio), 1) . ')', 'rows' => [
                        ['vs fastest', fmt_ratio($ratio)],
                        ['Rank', "#$rank of " . count($t['ranked'])],
                    ]],
            ];
        }
        $heat[$id] = ['test' => $t, 'cells' => $cells];
    }

    return [
        'title' => 'Overview',
        'view'  => 'overview',
        'nav'   => '/',
        'vars'  => compact('ranking', 'ranked', 'partial', 'stats', 'heat'),
    ];
}

function page_test(array $r, string $id): array
{
    $t = $r['tests'][$id];
    $stats = [];
    if ($t['times']) {
        $fast = $t['ranked'][0];
        $slow = end($t['ranked']);
        $stats[] = ['Fastest', fmt_ms($t['best']), lang_name($fast) . ' · median'];
        $stats[] = ['Slowest', fmt_ms($t['times'][$slow]['median']),
            lang_name($slow) . ' · ' . fmt_ratio($t['times'][$slow]['median'] / $t['best']) . ' the fastest'];
    }
    if ($small = smallest_binary([$t])) {
        $stats[] = ['Smallest binary', fmt_size($small['bytes']), lang_name($small['lang']) . " · {$small['profile']}"];
        $big = smallest_binary([$t], largest: true);
        $stats[] = ['Largest binary', fmt_size($big['bytes']), lang_name($big['lang']) . " · {$big['profile']}"];
    }

    // Table rows: timed languages fastest first, then any that only built.
    $order = array_merge($t['ranked'], array_values(array_diff($t['langs'], $t['ranked'])));

    $parts = [$t['category'] === '' ? null : $t['category'], $t['langs'] ? plural(count($t['langs']), 'language') : null];
    $chips = [];
    if ($t['args'] !== '') {
        $chips[] = ['args', $t['args']];
    }
    $chips[] = [$t['ranAt'] ? 'Run ' . fmt_date($t['ranAt']) : 'Not run yet', ''];

    $crumbs = [['Overview', '/']];
    if ($t['category'] !== '') {
        $crumbs[] = [$t['category'], null];
    }
    $crumbs[] = [$t['name'], null];

    return [
        'title' => $t['name'],
        'view'  => 'test',
        'nav'   => test_url($id),
        'vars'  => [
            't'           => $t,
            'stats'       => $stats,
            'order'       => $order,
            'description' => implode(' · ', array_filter($parts)),
            'chips'       => $chips,
            'crumbs'      => $crumbs,
            'hasRss'      => (bool) array_filter($t['times'], fn ($s) => $s['rss'] !== null),
        ],
    ];
}

function page_languages(array $r): array
{
    $ranking = language_ranking($r);
    $ranked = array_values(array_filter($ranking, fn ($s) => $s['place'] !== null));
    $stats = [];
    if ($ranked) {
        $stats[] = ['Best overall', lang_name($ranked[0]['lang']), fmt_ratio($ranked[0]['score']) . ' the fastest'];
    }
    $pick = function (string $label, callable $value, callable $note, bool $max = false) use ($ranking, &$stats) {
        $have = array_values(array_filter($ranking, fn ($s) => $value($s) !== null));
        if (!$have) {
            return;
        }
        usort($have, fn ($a, $b) => $max ? $value($b) <=> $value($a) : $value($a) <=> $value($b));
        $stats[] = [$label, lang_name($have[0]['lang']), $note($have[0])];
    };
    $pick('Most wins', fn ($s) => $s['wins'] ?: null, fn ($s) => "{$s['wins']} of {$r['timed']} tests", max: true);
    $pick('Smallest binaries', fn ($s) => $s['size']['release'], fn ($s) => fmt_size($s['size']['release']) . ' typical release');
    $pick('Fastest builds', fn ($s) => $s['build']['release'], fn ($s) => fmt_ms($s['build']['release']) . ' median release build');

    $max = max([0.0, ...array_map(fn ($s) => $s['score'] ?? 0.0, $ranking)]);
    return [
        'title' => 'Languages',
        'view'  => 'languages',
        'nav'   => '/languages',
        'vars'  => ['ranking' => $ranking, 'stats' => $stats, 'max' => $max, 'crumbs' => [['Overview', '/'], ['Languages', null]]],
    ];
}

function page_language(array $r, string $lang): array
{
    $ranking = language_ranking($r);
    $s = current(array_filter($ranking, fn ($e) => $e['lang'] === $lang));
    $rankedCount = count(array_filter($ranking, fn ($e) => $e['place'] !== null));

    $timed = array_filter($s['rows'], fn ($row) => $row['ratio'] !== null);
    $stats = [];
    if ($s['score'] !== null) {
        $stats[] = ['Score', fmt_ratio($s['score']), 'geometric mean vs fastest'];
        $stats[] = ['Wins', (string) $s['wins'], "of {$s['timed']} tests timed"];
        uasort($timed, fn ($a, $b) => $a['ratio'] <=> $b['ratio']);
        $best = reset($timed);
        $worst = end($timed);
        $stats[] = ['Best result', fmt_ratio($best['ratio']), "{$best['test']['name']} · #{$best['rank']} of {$best['of']}"];
        $stats[] = ['Worst result', fmt_ratio($worst['ratio']), "{$worst['test']['name']} · #{$worst['rank']} of {$worst['of']}"];
    }

    $standing = match (true) {
        $s['place'] !== null => "ranked #{$s['place']} of $rankedCount",
        $s['timed'] > 0      => 'not ranked: not timed on every test',
        default              => 'not timed yet',
    };
    return [
        'title' => lang_name($lang),
        'view'  => 'language',
        'nav'   => lang_url($lang),
        'vars'  => [
            's'           => $s,
            'stats'       => $stats,
            'description' => "{$s['timed']} of {$r['timed']} tests · $standing",
            'chips'       => $s['version'] === null ? [] : [['', $s['version']]],
            'crumbs'      => [['Overview', '/'], ['Languages', '/languages'], [lang_name($lang), null]],
        ],
    ];
}

function page_toolchains(array $r): array
{
    return [
        'title' => 'Toolchains',
        'view'  => 'toolchains',
        'nav'   => '/toolchains',
        'vars'  => ['crumbs' => [['Overview', '/'], ['Toolchains', null]]],
    ];
}

function page_not_found(string $path): array
{
    return [
        'title'  => 'Not found',
        'view'   => 'not-found',
        'vars'   => ['path' => $path, 'crumbs' => [['Overview', '/'], ['Not found', null]]],
        'status' => 404,
    ];
}
