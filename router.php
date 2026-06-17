<?php
/**
 * Clean URL router.
 *
 * Works for the PHP built-in server and for Apache rewrites in subfolders.
 */

$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

$basePath = rtrim(str_replace('/router.php', '', $scriptName), '/');
if ($basePath !== '' && strpos($requestPath, $basePath) === 0) {
    $requestPath = substr($requestPath, strlen($basePath)) ?: '/';
}

$uri = '/' . trim(rawurldecode($requestPath), '/');
$route = trim($uri, '/');

if ($route === '') {
    require __DIR__ . '/homepage.php';
    exit;
}

if (strpos($route, '..') !== false || strpos($route, "\0") !== false) {
    http_response_code(403);
    require __DIR__ . '/403.php';
    exit;
}

if (preg_match('#(^|/)(db|csrf|router|config)(\.php)?$#i', $route)
    || preg_match('#\.(sql|env|log|bak|ini|db)$#i', $route)
    || preg_match('#(^|/)vendor/#i', $route)
) {
    http_response_code(403);
    require __DIR__ . '/403.php';
    exit;
}

$file = __DIR__ . '/' . $route;

if (is_file($file) && pathinfo($file, PATHINFO_EXTENSION) !== 'php') {
    return false;
}

if (preg_match('/\.php$/i', $route)) {
    $cleanRoute = preg_replace('/\.php$/i', '', $route);
    $query = $_SERVER['QUERY_STRING'] ?? '';
    header('Location: ' . $basePath . '/' . $cleanRoute . ($query ? '?' . $query : ''), true, 301);
    exit;
}

$phpFile = $file . '.php';
if (!is_file($phpFile)) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

$adminOnlyPrefixes = ['admin/', 'api/admin_', 'export/'];
foreach ($adminOnlyPrefixes as $prefix) {
    if (strpos($route, $prefix) === 0) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (($_SESSION['role'] ?? '') !== 'admin') {
            http_response_code(403);
            require __DIR__ . '/403.php';
            exit;
        }

        break;
    }
}

require $phpFile;
