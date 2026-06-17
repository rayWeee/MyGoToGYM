<?php
include 'api/db.php';

// If accessed directly with .php extension, redirect to clean URL
if (basename($_SERVER['PHP_SELF']) === 'impressum.php') {
    header("Location: " . $basePath . "/impressum", true, 301);
    exit;
}

include 'views/impressum.html';