<?php
header('Content-Type: application/json');
require 'db.php';
require_once 'csrf.php';

// Session is started in db.php
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Login required"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !is_array($data) || !check_csrf($data['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Invalid security token"]);
    exit;
}
$class_id = filter_var($data['class_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
if (!$class_id) {
    echo json_encode(["success" => false, "message" => "Invalid class"]);
    exit;
}
$user_id = $_SESSION['user_id'];

// CHECK IF EVENT IS IN THE PAST
$stmt_check = $conn->prepare("SELECT start_datetime FROM classes WHERE id = ?");
$stmt_check->bind_param("i", $class_id);
$stmt_check->execute();
$class_row = $stmt_check->get_result()->fetch_assoc();
if(!$class_row || strtotime($class_row['start_datetime']) < time()){
    die(json_encode(["success" => false, "message" => "Cannot cancel reservation for a past workout."]));
}

$stmt = $conn->prepare("DELETE FROM reservations WHERE user_id = ? AND class_id = ?");
$stmt->bind_param("ii", $user_id, $class_id);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Reservation cancelled!"]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to cancel reservation."]);
}
?>
