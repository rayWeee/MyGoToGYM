<?php
header('Content-Type: application/json');
include 'db.php';
require_once 'csrf.php';

// Security: Use session ID, not the ID sent from the browser
if(!isset($_SESSION['user_id'])){
    die(json_encode(["success"=>false,"message"=>"You must be logged in to reserve."]));
}

$data = json_decode(file_get_contents("php://input"), true);
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !is_array($data) || !check_csrf($data['csrf_token'] ?? '')) {
    http_response_code(403);
    die(json_encode(["success" => false, "message" => "Invalid security token"]));
}
$class_id = filter_var($data['class_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
if (!$class_id) {
    die(json_encode(["success" => false, "message" => "Invalid class"]));
}
$user_id = $_SESSION['user_id'];

$conn->begin_transaction();
try {

// CHECK IF EVENT IS IN THE PAST
$stmt_check = $conn->prepare("SELECT start_datetime, capacity FROM classes WHERE id = ? FOR UPDATE");
$stmt_check->bind_param("i", $class_id);
$stmt_check->execute();
$class_row = $stmt_check->get_result()->fetch_assoc();
if(!$class_row || strtotime($class_row['start_datetime']) < time()){
    $conn->rollback();
    die(json_encode(["success" => false, "message" => "This workout has already started or passed."]));
}

// CHECK FOR ACTIVE MEMBERSHIP
$mem_stmt = $conn->prepare("SELECT membership_expiry, membership_type FROM users WHERE id = ?");
$mem_stmt->bind_param("i", $user_id);
$mem_stmt->execute();
$user_mem = $mem_stmt->get_result()->fetch_assoc();

if (!$user_mem || empty($user_mem['membership_expiry']) || strtotime($user_mem['membership_expiry']) < time()) {
    $conn->rollback();
    die(json_encode(["success" => false, "message" => "An active membership is required to reserve classes."]));
}

if (!in_array($user_mem['membership_type'], ['advanced', 'pro'])) {
    $conn->rollback();
    die(json_encode(["success" => false, "message" => "Your current plan does not include class reservations. Please upgrade to Advanced or Pro."]));
}

// CHECK ALREADY RESERVED
$check = $conn->prepare("
SELECT id FROM reservations
WHERE user_id=? AND class_id=?
");
$check->bind_param("ii", $user_id, $class_id);
$check->execute();

if($check->get_result()->num_rows > 0){
    $conn->rollback();
    die(json_encode([
"success"=>false,
"message"=>"You already reserved this workout"
]));
}

// CHECK CAPACITY
$cap_stmt = $conn->prepare("
SELECT c.capacity, COUNT(r.id) AS booked
FROM classes c
LEFT JOIN reservations r ON c.id = r.class_id
WHERE c.id = ?
GROUP BY c.id
");
$cap_stmt->bind_param("i", $class_id);
$cap_stmt->execute();
$cap = $cap_stmt->get_result()->fetch_assoc();

if(!$cap || $cap['booked'] >= $cap['capacity']){
    $conn->rollback();
die(json_encode([
"success"=>false,
"message"=>"Class is full"
]));
}

// INSERT
$stmt = $conn->prepare("
INSERT INTO reservations(class_id,user_id)
VALUES(?,?)
");

$stmt->bind_param("ii",$class_id,$user_id);

if ($stmt->execute()) {
    $conn->commit();
    echo json_encode([
        "success"=>true,
        "message"=>"Reserved successfully",
    ]);
} else {
    $conn->rollback();
    echo json_encode([
        "success"=>false,
        "message"=>"Reservation failed."
    ]);
}
} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    error_log('Reservation failed: ' . $e->getMessage());
    echo json_encode(["success" => false, "message" => "Reservation failed."]);
}
