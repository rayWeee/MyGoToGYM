<?php
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . $basePath . "/login");
    exit;
}

$plan = $_GET['plan'] ?? '';
$user_id = $_SESSION['user_id'];
$valid_plans = ['basic', 'advanced', 'pro'];

if (in_array($plan, $valid_plans)) {
    $expiry = date('Y-m-d H:i:s', strtotime('+31 days'));
    $stmt = $conn->prepare("
        UPDATE users
        SET membership_type = ?, membership_expiry = ?
        WHERE id = ?
    ");
    $stmt->bind_param("ssi", $plan, $expiry, $user_id);
    $stmt->execute();

    header("Location: " . $basePath . "/homepage?success=1");
} else {
    header("Location: " . $basePath . "/homepage?error=invalid_return");
}
