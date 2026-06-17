<?php

// If accessed directly with .php extension, redirect to clean URL
if (basename($_SERVER['PHP_SELF']) === '403.php') {
    header("Location: " . $basePath . "/403", true, 301);
    exit;
}

include 'api/db.php';
include 'views/403.html';