<?php
// Chart components. Each returns escaped HTML; app.js adds the tooltips.
declare(strict_types=1);

/**
 * Tooltip payload for app.js: a headline value, a label with a color key,
 * and optional key/value rows. `color` is any CSS color; default series 1.
 * @param array{value: string, label: string, rows?: list<array{string, string}>, color?: string} $tip
 */
function tip_attrs(array $tip): string
{
    return sprintf(' data-tip="%s"', h(json_encode($tip, JSON_UNESCAPED_UNICODE)));
}

/**
 * Horizontal bar chart: a labelled row per item, one bar per series, all on
 * one linear scale from zero.
 * @param list<array{label: string, href?: string, bars: list<array{series: int, value: float, text: string, tip: array}>}> $rows
 */
function bar_chart(array $rows, string $reserve = '10ch', string $class = ''): string
{
    $max = 0.0;
    foreach ($rows as $row) {
        foreach ($row['bars'] as $bar) {
            $max = max($max, $bar['value']);
        }
    }
    $out = '<div class="bars' . ($class === '' ? '' : ' ' . h($class)) . '">';
    foreach ($rows as $row) {
        $label = isset($row['href'])
            ? '<a href="' . h($row['href']) . '">' . h($row['label']) . '</a>'
            : h($row['label']);
        $out .= '<div class="bar-row"><div class="bar-label">' . $label . '</div><div class="bar-stack">';
        foreach ($row['bars'] as $bar) {
            $f = $max > 0 ? $bar['value'] / $max : 0;
            $tip = $bar['tip'] + ['color' => "var(--s{$bar['series']})"];
            $out .= sprintf(
                '<div class="bar-track" tabindex="0"%s aria-label="%s" style="--f:%.4f;--reserve:%s">'
                . '<div class="bar s%d"></div><span class="bar-value">%s</span></div>',
                tip_attrs($tip), h($tip['label'] . ': ' . $tip['value']), $f, h($reserve),
                $bar['series'], h($bar['text'])
            );
        }
        $out .= '</div></div>';
    }
    return $out . '</div>';
}

/**
 * Release (series 1) and min (series 2) bars per language, smallest release first.
 * @param array<string, array<string, float>> $values lang => profile => value
 */
function profile_chart(array $values, callable $fmt, string $unit, ?callable $href = null): string
{
    uasort($values, fn ($a, $b) => ($a['release'] ?? INF) <=> ($b['release'] ?? INF));
    $rows = [];
    foreach ($values as $lang => $profiles) {
        $bars = [];
        foreach (['release' => 1, 'min' => 2] as $profile => $series) {
            if (!isset($profiles[$profile])) {
                continue;
            }
            $v = $profiles[$profile];
            $bars[] = [
                'series' => $series, 'value' => $v, 'text' => $fmt($v),
                'tip' => ['value' => $fmt($v), 'label' => lang_name($lang) . " · $profile",
                    'rows' => [[$unit, number_format($v)]]],
            ];
        }
        $rows[] = ['label' => lang_name($lang), 'href' => $href ? $href($lang) : null, 'bars' => $bars];
    }
    return bar_chart(array_map(fn ($r) => array_filter($r, fn ($v) => $v !== null), $rows));
}

function size_chart(array $t): string
{
    return profile_chart($t['sizes'], 'fmt_size', 'Bytes', 'lang_url');
}

function build_chart(array $t): string
{
    return profile_chart($t['build_ms'], 'fmt_ms', 'Milliseconds', 'lang_url');
}

function time_chart(array $t): string
{
    $rows = [];
    foreach ($t['times'] as $lang => $s) {
        $rel = fmt_ratio($s['median'] / $t['best']);
        $rows[] = ['label' => lang_name($lang), 'href' => lang_url($lang), 'bars' => [[
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
        $rows[] = ['label' => lang_name($lang), 'href' => lang_url($lang), 'bars' => [[
            'series' => 1, 'value' => $s['rss'], 'text' => fmt_mem($s['rss']),
            'tip' => ['value' => fmt_mem($s['rss']), 'label' => lang_name($lang) . ' · peak RSS', 'rows' => []],
        ]]];
    }
    return bar_chart($rows);
}

/** One language: its median ÷ the fastest median, per timed test. */
function slowdown_chart(array $summary): string
{
    $rows = [];
    foreach ($summary['rows'] as $id => $r) {
        if ($r['ratio'] === null) {
            continue;
        }
        $place = "#{$r['rank']} of {$r['of']}";
        $rows[] = ['label' => $r['test']['name'], 'href' => test_url($id), 'bars' => [[
            'series' => 1, 'value' => $r['ratio'], 'text' => fmt_ratio($r['ratio']) . " · $place",
            'tip' => ['value' => fmt_ratio($r['ratio']), 'label' => $r['test']['id'], 'rows' => [
                ['Median', fmt_ms($r['times']['median'])],
                ['Fastest', lang_name($r['test']['ranked'][0]) . ' · ' . fmt_ms($r['test']['best'])],
                ['Rank', $place],
            ]],
        ]]];
    }
    return bar_chart($rows, '16ch', 'wide');
}

/** Ranked languages by score. @param list<array> $ranked summaries with a place */
function ranking_chart(array $ranked): string
{
    $rows = [];
    foreach ($ranked as $s) {
        $rows[] = ['label' => lang_name($s['lang']), 'href' => lang_url($s['lang']), 'bars' => [[
            'series' => 1, 'value' => $s['score'], 'text' => fmt_ratio($s['score']) . ' · ' . plural($s['wins'], 'win'),
            'tip' => ['value' => fmt_ratio($s['score']), 'label' => '#' . $s['place'] . ' · ' . lang_name($s['lang']), 'rows' => [
                ['Wins', (string) $s['wins']],
                ['Tests timed', (string) $s['timed']],
            ]],
        ]]];
    }
    return bar_chart($rows, '14ch');
}

/** Median binary size per language across its tests. @param list<array> $summaries */
function typical_size_chart(array $summaries): string
{
    $values = [];
    foreach ($summaries as $s) {
        $values[$s['lang']] = array_filter($s['size'], fn ($v) => $v !== null);
    }
    return profile_chart(array_filter($values), 'fmt_size', 'Bytes', 'lang_url');
}
