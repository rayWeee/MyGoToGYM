<?php
header('Content-Type: application/json');
include 'db.php';

// Check if user is logged in and has the correct role
if(!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'trainer'])){
    die(json_encode(["success"=>false,"message"=>"Unauthorized access"]));
}

$data = json_decode(file_get_contents("php://input"), true);

$id = $data['id'];

$stmt = $conn->prepare("DELETE FROM reservations WHERE class_id=?");
$stmt->bind_param("i",$id);
$stmt->execute();

$stmt2 = $conn->prepare("DELETE FROM classes WHERE id=?");
$stmt2->bind_param("i",$id);
$stmt2->execute();

echo json_encode(["success"=>true]);