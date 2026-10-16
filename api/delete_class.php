<?php
header('Content-Type: application/json');
include 'db.php';
require_once 'csrf.php';

// Check if user is logged in and has the correct role
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'trainer'], true)) {
    die(json_encode(["success"=>false,"message"=>"Unauthorized access"]));
}

$data = json_decode(file_get_contents("php://input"), true);
if (!is_array($data) || !check_csrf($data['csrf_token'] ?? '')) {
    http_response_code(403);
    die(json_encode(["success" => false, "message" => "Invalid request."]));
}
$id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
if (!$id) {
    die(json_encode(["success" => false, "message" => "Invalid class."]));
}

$roleStmt = $conn->prepare('SELECT role FROM users WHERE id = ?');
$roleStmt->bind_param('i', $_SESSION['user_id']);
$roleStmt->execute();
$currentRole = $roleStmt->get_result()->fetch_assoc()['role'] ?? '';
if (!in_array($currentRole, ['admin', 'trainer'], true)) {
    http_response_code(403);
    die(json_encode(["success" => false, "message" => "Unauthorized access"]));
}
$_SESSION['role'] = $currentRole;

if ($currentRole === 'trainer') {
    $owner = $conn->prepare('SELECT id FROM classes WHERE id = ? AND created_by = ?');
    $owner->bind_param('ii', $id, $_SESSION['user_id']);
    $owner->execute();
    if ($owner->get_result()->num_rows === 0) {
        http_response_code(403);
        die(json_encode(["success" => false, "message" => "You can only delete your own classes."]));
    }
}

try {
    $conn->begin_transaction();
    $stmt = $conn->prepare("DELETE FROM reservations WHERE class_id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $stmt2 = $conn->prepare("DELETE FROM classes WHERE id=?");
    $stmt2->bind_param("i", $id);
    $stmt2->execute();
    $conn->commit();
    echo json_encode(["success" => true]);
} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    error_log('Delete class failed: ' . $e->getMessage());
    echo json_encode(["success" => false, "message" => "Unable to delete the class."]);
}
