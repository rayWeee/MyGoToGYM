<?php
header('Content-Type: application/json');
include 'db.php';

// Security: Use session ID, not the ID sent from the browser
if(!isset($_SESSION['user_id'])){
    die(json_encode(["success"=>false,"message"=>"You must be logged in to reserve."]));
}

$data = json_decode(file_get_contents("php://input"), true);
$class_id = intval($data['class_id'] ?? 0);
$user_id = $_SESSION['user_id'];

// CHECK IF EVENT IS IN THE PAST
$stmt_check = $conn->prepare("SELECT start_datetime FROM classes WHERE id = ?");
$stmt_check->bind_param("i", $class_id);
$stmt_check->execute();
$class_row = $stmt_check->get_result()->fetch_assoc();
if(!$class_row || strtotime($class_row['start_datetime']) < time()){
    die(json_encode(["success" => false, "message" => "This workout has already started or passed."]));
}

// CHECK FOR ACTIVE MEMBERSHIP
$mem_stmt = $conn->prepare("SELECT membership_expiry, membership_type FROM users WHERE id = ?");
$mem_stmt->bind_param("i", $user_id);
$mem_stmt->execute();
$user_mem = $mem_stmt->get_result()->fetch_assoc();

if (!$user_mem || empty($user_mem['membership_expiry']) || strtotime($user_mem['membership_expiry']) < time()) {
    die(json_encode(["success" => false, "message" => "An active membership is required to reserve classes."]));
}

if (!in_array($user_mem['membership_type'], ['advanced', 'pro'])) {
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

if($cap['booked'] >= $cap['capacity']){
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
    echo json_encode([
        "success"=>true,
        "message"=>"Reserved successfully",
    ]);
} else {
    echo json_encode([
        "success"=>false,
        "message"=>"Reservation failed."
    ]);
}