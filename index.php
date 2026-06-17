<?php
/**
 * Schedule page and fallback clean-URL router.
 *
 * Some local servers use index.php as the router script for every request.
 * In that setup, /login or /contacts would otherwise land on the schedule.
 */

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$basePath = rtrim(str_replace('/index.php', '', $scriptName), '/');

if (PHP_SAPI === 'cli-server') {
    $staticFile = __DIR__ . rawurldecode($requestPath);
    if (is_file($staticFile)) {
        return false;
    }
}

if ($basePath !== '' && strpos($requestPath, $basePath) === 0) {
    $requestPath = substr($requestPath, strlen($basePath)) ?: '/';
}

$route = trim(rawurldecode($requestPath), '/');

if ($route === '' || strtolower($route) === 'index.php') {
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

if (preg_match('/\.php$/i', $route)) {
    $cleanRoute = preg_replace('/\.php$/i', '', $route);
    $query = $_SERVER['QUERY_STRING'] ?? '';
    header('Location: ' . $basePath . '/' . $cleanRoute . ($query ? '?' . $query : ''), true, 301);
    exit;
}

if ($route !== 'index') {
    $phpFile = __DIR__ . '/' . $route . '.php';
    if (is_file($phpFile)) {
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
        exit;
    }

    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

include 'api/db.php';

// $username and $role are provided by api/nav.php.
include 'views/index.html';


// C:\xampp\php\php.exe -S localhost:8080        