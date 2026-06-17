<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../api/db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    echo json_encode(["success"=>false,"message"=>"Access denied"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$id = intval($data['user_id'] ?? 0);

if ($id <= 0) {
    echo json_encode(["success" => false, "message" => "Invalid user"]);
    exit;
}

if($id == $_SESSION['user_id']){
    echo json_encode(["success"=>false,"message"=>"Cannot delete yourself"]);
    exit;
}

$stmt = $conn->prepare("DELETE FROM users WHERE id=?");
$stmt->bind_param("i",$id);

if($stmt->execute()){
    echo json_encode(["success"=>true,"message"=>"User deleted"]);
}else{
    echo json_encode(["success"=>false,"message"=>"Delete failed"]);
}
