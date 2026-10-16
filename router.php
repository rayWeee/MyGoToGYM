<?php
/**
 * Clean URL router.
 *
 * Works for the PHP built-in server and for Apache rewrites in subfolders.
 */

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$appRoot = str_replace('\\', '/', realpath(__DIR__) ?: __DIR__);
$documentRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '');
$basePath = ($documentRoot !== '' && strpos($appRoot, $documentRoot) === 0)
    ? rtrim(substr($appRoot, strlen($documentRoot)), '/')
    : '';
if ($basePath !== '' && strpos($requestPath, $basePath) === 0) {
    $requestPath = substr($requestPath, strlen($basePath)) ?: '/';
}

$uri = '/' . trim(rawurldecode($requestPath), '/');
$route = trim($uri, '/');

// Language-prefixed public URLs: /lv/homepage, /ru/index, /en/contacts.
if (preg_match('#^(en|lv|ru)(?:/(.*))?$#', $route, $matches)) {
    $_GET['lang'] = $matches[1];
    $route = trim($matches[2] ?? '', '/');
}

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
            require_once __DIR__ . '/api/session.php';
            start_app_session();
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
