<?php
// Template rendering and the small HTML helpers templates share.
declare(strict_types=1);

/** Render views/<name>.php with $vars in scope and return the HTML. */
function render(string $__view, array $__vars = []): string
{
    $__file = DASHBOARD_DIR . "/views/$__view.php";
    if (!is_file($__file)) {
        throw new RuntimeException("missing view: $__view");
    }
    extract($__vars, EXTR_SKIP);
    ob_start();
    try {
        require $__file;
        return (string) ob_get_clean();
    } catch (Throwable $e) {
        ob_end_clean();
        throw $e;
    }
}

const ICONS = [
    'overview' => '<rect x="2.5" y="2.5" width="4.5" height="4.5" rx="1"/><rect x="9" y="2.5" width="4.5" height="4.5" rx="1"/>'
        . '<rect x="2.5" y="9" width="4.5" height="4.5" rx="1"/><rect x="9" y="9" width="4.5" height="4.5" rx="1"/>',
    'ranking'  => '<path d="M3 4h6M3 8h10M3 12h4"/>',
    'tools'    => '<rect x="4" y="4" width="8" height="8" rx="1.5"/><path d="M6.5 1.5v2.5M9.5 1.5v2.5M6.5 12v2.5M9.5 12v2.5'
        . 'M1.5 6.5h2.5M1.5 9.5h2.5M12 6.5h2.5M12 9.5h2.5"/>',
    'menu'     => '<path d="M2.5 4.5h11M2.5 8h11M2.5 11.5h11"/>',
];

function icon(string $name): string
{
    return '<svg viewBox="0 0 16 16" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.5"'
        . ' stroke-linecap="round" aria-hidden="true">' . ICONS[$name] . '</svg>';
}

function nav_link(string $href, string $label, string $current, string $icon = ''): string
{
    return sprintf(
        '<a class="nav-link" href="%s"%s>%s<span>%s</span></a>',
        h($href), $href === $current ? ' aria-current="page"' : '', $icon === '' ? '' : icon($icon), h($label)
    );
}

/** A chip: plain text, optionally followed by a code value. */
function chip(string $text, string $code = ''): string
{
    $parts = array_filter([h($text), $code === '' ? '' : '<code>' . h($code) . '</code>'], fn ($p) => $p !== '');
    return '<span class="chip">' . implode(' ', $parts) . '</span>';
}

/** Value or an en dash when missing. */
function or_dash(mixed $value, callable $fmt): string
{
    return $value === null ? '–' : h($fmt($value));
}
