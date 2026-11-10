<?php
// Routing: maps a request path to a page and renders it inside the layout.
declare(strict_types=1);

/**
 * @return array{0: string, 1: array<string, string>}|null page name and its arguments,
 *         or null when nothing matches. Ids are checked against the results, so
 *         a path never reaches the filesystem.
 */
function route(string $path, array $results): ?array
{
    if ($path === '/') {
        return ['overview', []];
    }
    if ($path === '/languages' || $path === '/toolchains') {
        return [substr($path, 1), []];
    }
    if (preg_match('#^/languages/([^/]+)$#', $path, $m) && in_array($m[1], $results['langs'], true)) {
        return ['language', ['lang' => $m[1]]];
    }
    if (preg_match('#^/tests/(.+)$#', $path, $m) && isset($results['tests'][$m[1]])) {
        return ['test', ['id' => $m[1]]];
    }
    return null;
}

/** Normalised request path: decoded, no trailing slash except the root. */
function request_path(string $uri): string
{
    $path = rawurldecode((string) parse_url($uri, PHP_URL_PATH));
    return '/' . trim($path, '/');
}

/** @return array{status: int, html: string} */
function handle(string $uri): array
{
    $path = request_path($uri);
    $results = load_results(BENCH_ROOT);
    $route = route($path, $results);
    $page = match ($route[0] ?? null) {
        'overview'   => page_overview($results),
        'test'       => page_test($results, $route[1]['id']),
        'languages'  => page_languages($results),
        'language'   => page_language($results, $route[1]['lang']),
        'toolchains' => page_toolchains($results),
        default      => page_not_found($path),
    };
    $html = render('layout', [
        'page'    => $page,
        'results' => $results,
        'current' => $page['nav'] ?? $path,
        'content' => render('pages/' . $page['view'], $page['vars'] + ['results' => $results, 'title' => $page['title']]),
    ]);
    return ['status' => $page['status'] ?? 200, 'html' => $html];
}
