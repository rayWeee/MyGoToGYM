<?php
include 'api/db.php';

// If accessed directly with .php extension, redirect to clean URL
if (basename($_SERVER['PHP_SELF']) === 'privacy.php') {
    header("Location: " . $basePath . "/privacy", true, 301);
    exit;
}

include 'views/privacy.html';