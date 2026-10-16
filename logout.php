<?php
require_once __DIR__ . '/api/session.php';
start_app_session();

$_SESSION = [];
setcookie(session_name(), '', time() - 42000, '/');
session_destroy();

// If accessed directly with .php extension, redirect to clean URL
if (basename($_SERVER['PHP_SELF']) === 'logout.php') {
    header("Location: " . $basePath . "/logout", true, 301);
    exit;
}

header("Location: " . $basePath . "/login");
exit;
