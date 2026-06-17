<?php
require_once 'db.php';
require_once 'csrf.php';
header('Content-Type: application/json');

// Security Check: Only users with the 'admin' role can access this script
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

// CSRF Check: Ensure the request came from your dashboard
if (!isset($data['csrf_token']) || !check_csrf($data['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid security token']);
    exit;
}

$target_user_id = intval($data['user_id'] ?? 0);
$plan = $data['plan'] ?? '';

$valid_plans = ['basic', 'advanced', 'pro', 'none'];

if ($target_user_id > 0 && in_array($plan, $valid_plans)) {
    if ($plan === 'none') {
        // Remove membership
        $stmt = $conn->prepare("UPDATE users SET membership_type = NULL, membership_expiry = NULL WHERE id = ?");
        $stmt->bind_param("i", $target_user_id);
    } else {
        // Assign membership for 31 days
        $expiry = date('Y-m-d H:i:s', strtotime('+31 days'));
        $stmt = $conn->prepare("UPDATE users SET membership_type = ?, membership_expiry = ? WHERE id = ?");
        $stmt->bind_param("ssi", $plan, $expiry, $target_user_id);
    }
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Membership successfully assigned to user ID ' . $target_user_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database update failed']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID or membership plan']);
}
exit;