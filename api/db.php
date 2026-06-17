<?php
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
$conn = new mysqli("localhost", "root", "", "gym_tracker2");
if ($conn->connect_error) {
    die("DB Error: " . $conn->connect_error);
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_GET['lang'])) {
    $l = strtolower($_GET['lang']);
    if (in_array($l, ['en', 'lv', 'ru'])) {
        $_SESSION['lang'] = $l;
    }
}
$current_lang = $_SESSION['lang'] ?? 'en';
$txt = include __DIR__ . '/lang.php';
$t = $txt[$current_lang];
?>
