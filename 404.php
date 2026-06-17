<?php

// If accessed directly with .php extension, redirect to clean URL
if (basename($_SERVER['PHP_SELF']) === '404.php') {
    header("Location: " . $basePath . "/404", true, 301);
    exit;
}

include 'api/db.php';
include 'views/404.html';