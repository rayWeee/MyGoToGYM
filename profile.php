<?php
include 'api/db.php';

// If accessed directly with .php extension, redirect to clean URL
if (basename($_SERVER['PHP_SELF']) === 'profile.php') {
    header("Location: " . $basePath . "/profile", true, 301);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    header("Location: " . $basePath . "/login");
    exit;
}

require_once __DIR__ . '/api/csrf.php';

$stmt = $conn->prepare("SELECT name, email, phone, role, membership_type, membership_expiry FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    header("Location: logout.php");
    exit;
}

// Fetch username for header display
$username = $user['name'] ?? '';

// Fetch payment history
$stmt_p = $conn->prepare("SELECT plan_type, amount, status, created_at FROM payments WHERE user_id = ? ORDER BY created_at DESC");
$stmt_p->bind_param("i", $_SESSION['user_id']);
$stmt_p->execute();
$payments = $stmt_p->get_result();

$csrf_token = generate_csrf();

include 'views/profile.html';