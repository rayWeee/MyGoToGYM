<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../api/db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    echo json_encode(["success"=>false,"message"=>"Access denied"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$id = intval($data['user_id'] ?? 0);
$name = trim($data['new_name'] ?? '');

if ($id <= 0 || $name === '') {
    echo json_encode(["success" => false, "message" => "Invalid user name"]);
    exit;
}

$stmt = $conn->prepare("UPDATE users SET name=? WHERE id=?");
$stmt->bind_param("si",$name,$id);

if($stmt->execute()){
    echo json_encode(["success"=>true,"message"=>"Name updated"]);
}else{
    echo json_encode(["success"=>false,"message"=>"Update failed"]);
}
