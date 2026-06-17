<?php
header('Content-Type: application/json');
require 'db.php';

if (!isset($_GET['class_id'])) {
    echo json_encode([]);
    exit;
}

$class_id = intval($_GET['class_id']);

// Fetch names of users who have a 'reserved' status for this class
$stmt = $conn->prepare("
    SELECT u.id, u.name 
    FROM users u
    JOIN reservations r ON u.id = r.user_id
    WHERE r.class_id = ? AND r.status = 'reserved'
");
$stmt->bind_param("i", $class_id);
$stmt->execute();
$result = $stmt->get_result();

$participants = [];
while($row = $result->fetch_assoc()){
    $participants[] = $row;
}

echo json_encode($participants);