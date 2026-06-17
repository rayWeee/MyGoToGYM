<?php
/**
 * Admin clean-URL fallback for servers that route nested paths to /admin first.
 */

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$route = trim(rawurldecode($requestPath), '/');

if (strpos($route, 'admin/') === 0) {
    $route = substr($route, strlen('admin/'));
} elseif ($route === 'admin') {
    $route = 'dashboard';
}

$route = trim($route, '/');

if ($route === '' || $route === 'index' || $route === 'dashboard') {
    require __DIR__ . '/dashboard.php';
    exit;
}

if (strpos($route, '..') !== false || strpos($route, "\0") !== false) {
    http_response_code(403);
    require __DIR__ . '/../403.php';
    exit;
}

if (preg_match('/\.php$/i', $route)) {
    $cleanRoute = preg_replace('/\.php$/i', '', $route);
    $query = $_SERVER['QUERY_STRING'] ?? '';
    $basePath = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'] ?? ''))), '/');
    header('Location: ' . $basePath . '/admin/' . $cleanRoute . ($query ? '?' . $query : ''), true, 301);
    exit;
}

$phpFile = __DIR__ . '/' . $route . '.php';
if (!is_file($phpFile)) {
    http_response_code(404);
    require __DIR__ . '/../404.php';
    exit;
}

require $phpFile;
