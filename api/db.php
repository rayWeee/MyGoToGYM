<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/session.php';
set_app_security_headers();

// Define $basePath if it's not already defined (e.g., when a file is accessed directly, not through router.php)
if (!isset($basePath)) {
    $appRoot = str_replace('\\', '/', dirname(__DIR__));
    $documentRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '');

    if ($documentRoot !== '' && strpos($appRoot, $documentRoot) === 0) {
        $basePath = substr($appRoot, strlen($documentRoot));
    } else {
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        $basePath = preg_replace('#/(admin|api|export)$#', '', $scriptDir);
    }

    $basePath = rtrim($basePath, '/');
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
if ($conn->connect_error) {
    error_log('Database connection failed: ' . $conn->connect_error);
    http_response_code(503);
    exit('Database unavailable.');
}
$conn->set_charset('utf8mb4');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    start_app_session();
}

if (isset($_GET['lang'])) {
    $l = strtolower($_GET['lang']);
    if (in_array($l, ['en', 'lv', 'ru'], true)) {
        $_SESSION['lang'] = $l;
    }
}
$current_lang = $_SESSION['lang'] ?? 'en';
$localizedBase = $basePath . '/' . $current_lang;
$txt = include __DIR__ . '/lang.php';
$t = $txt[$current_lang];
?>
