<?php
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . $basePath . "/login");
    exit;
}

// A browser query parameter is not payment proof. Membership changes must be made
// by a verified payment webhook (or the authenticated membership workflow).
header("Location: " . $basePath . "/homepage?error=invalid_return");
exit;
