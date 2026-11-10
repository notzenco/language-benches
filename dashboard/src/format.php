<?php
// Escaping, number formatting and URLs.
declare(strict_types=1);

function h(string|int|float|null $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES);
}

function lang_name(string $lang): string
{
    return LANG_NAMES[$lang] ?? $lang;
}

/** Known languages in LANG_NAMES order, then unknown ones alphabetically. */
function lang_order(array $langs): array
{
    $known = array_keys(LANG_NAMES);
    $key = fn ($l) => [array_search($l, $known, true) === false, array_search($l, $known, true), $l];
    usort($langs, fn ($a, $b) => $key($a) <=> $key($b));
    return $langs;
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

/** Three significant figures or so: 1.4 ms, 56.0 ms, 687 ms, 1.07 s. */
function fmt_ms(float $ms): string
{
    return match (true) {
        $ms >= 1000 => sprintf('%.2f s', $ms / 1000),
        $ms >= 100  => sprintf('%.0f ms', $ms),
        default     => sprintf('%.1f ms', $ms),
    };
}

/** Milliseconds without a unit, for heatmap cells: 1.4, 687, 1,065. */
function fmt_ms_bare(float $ms): string
{
    return $ms < 10 ? sprintf('%.1f', $ms) : number_format($ms);
}

function fmt_ratio(float $r): string
{
    return sprintf('%.2f×', $r);
}

function fmt_date(int $ts): string
{
    return date('j M Y, H:i', $ts);
}

/** "1 win", "3 wins". */
function plural(int $n, string $one, ?string $many = null): string
{
    return $n . ' ' . ($n === 1 ? $one : ($many ?? $one . 's'));
}

function test_url(string $id): string
{
    return '/tests/' . implode('/', array_map('rawurlencode', explode('/', $id)));
}

function lang_url(string $lang): string
{
    return '/languages/' . rawurlencode($lang);
}

/** Asset URL with its modification time, so a reload picks up edits. */
function asset_url(string $file): string
{
    $path = DASHBOARD_DIR . '/public/assets/' . $file;
    return '/assets/' . $file . (is_file($path) ? '?v=' . filemtime($path) : '');
}
