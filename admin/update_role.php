<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../api/db.php';
require_once __DIR__ . '/../api/csrf.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    echo json_encode(["success" => false, "message" => "Access denied"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !is_array($data) || !check_csrf($data['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Invalid security token"]);
    exit;
}

$user_id = intval($data['user_id'] ?? 0);
$new_role = $data['new_role'] ?? '';

// Define allowed roles
$valid_roles = ['user', 'trainer', 'admin'];
if ($user_id <= 0 || !in_array($new_role, $valid_roles, true)) {
    die(json_encode(["success" => false, "message" => "Invalid role selected"]));
}

$stmt = $conn->prepare("UPDATE users SET role=? WHERE id=?");
$stmt->bind_param("si", $new_role, $user_id);

$stmt->execute();

echo json_encode([
"success"=>true,
"message"=>"Role updated"
]);
