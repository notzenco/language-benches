<?php
// Front controller, and the router script for PHP's built-in server:
//   php -S 127.0.0.1:8765 -t dashboard/public dashboard/public/index.php
// (./dashboard-start.sh runs exactly that). Files under assets/ are served
// as they are; every other path is a dashboard page.
declare(strict_types=1);

$path = rawurldecode((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
if (PHP_SAPI === 'cli-server' && str_starts_with($path, '/assets/')) {
    $assets = realpath(__DIR__ . '/assets');
    $file = realpath(__DIR__ . $path);
    if ($assets !== false && $file !== false && str_starts_with($file, $assets . '/') && is_file($file)) {
        return false;
    }
}

require dirname(__DIR__) . '/src/bootstrap.php';

['status' => $status, 'html' => $html] = handle($_SERVER['REQUEST_URI'] ?? '/');
http_response_code($status);
header('Content-Type: text/html; charset=utf-8');
echo $html;
