<?php
header('Content-Type: application/json');
require 'db.php';

// Session is started in db.php
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Login required"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$class_id = intval($data['class_id']);
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