<?php
session_start();
session_unset();
session_destroy();

// If accessed directly with .php extension, redirect to clean URL
if (basename($_SERVER['PHP_SELF']) === 'logout.php') {
    header("Location: " . $basePath . "/logout", true, 301);
    exit;
}

header("Location: " . $basePath . "/login");
exit;
