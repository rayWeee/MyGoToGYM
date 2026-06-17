<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';

if (basename($_SERVER['PHP_SELF'] ?? '') === 'buy_membership.php') {
    $query = $_SERVER['QUERY_STRING'] ?? '';
    header("Location: " . $basePath . "/api/buy_membership" . ($query ? '?' . $query : ''), true, 301);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    header("Location: " . $basePath . "/login");
    exit;
}

$plan = $_POST['plan'] ?? $_GET['plan'] ?? '';
$validPlans = ['basic', 'advanced', 'pro'];

if (!in_array($plan, $validPlans, true)) {
    header("Location: " . $basePath . "/homepage?error=invalid_plan");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !check_csrf($_POST['csrf_token'] ?? '')) {
    header("Location: " . $basePath . "/membership_offer?plan=" . urlencode($plan) . "&error=csrf");
    exit;
}

$expiry = date('Y-m-d H:i:s', strtotime('+31 days'));
$stmt = $conn->prepare("
    UPDATE users
    SET membership_type = ?, membership_expiry = ?
    WHERE id = ?
");
$stmt->bind_param("ssi", $plan, $expiry, $_SESSION['user_id']);

if (!$stmt->execute()) {
    error_log('Membership update failed: ' . $conn->error);
    header("Location: " . $basePath . "/homepage?error=membership_failed");
    exit;
}

header("Location: " . $basePath . "/homepage?success=1");
exit;
