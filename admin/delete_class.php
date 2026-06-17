<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role']!=='admin') {
    echo json_encode(['success'=>false,'message'=>'Access denied']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? null;

if (!$id) {
    echo json_encode(['success'=>false,'message'=>'Invalid class ID']);
    exit;
}

// Optional: also remove from workouts if needed
$stmt = $conn->prepare("DELETE FROM classes WHERE id=?");
$stmt->bind_param("i", $id);
$success = $stmt->execute();

// Optional: remove associated workouts
$stmt2 = $conn->prepare("DELETE FROM workouts WHERE title=(SELECT title FROM classes WHERE id=?)");
$stmt2->bind_param("i", $id);
$stmt2->execute();

echo json_encode([
    'success'=>$success,
    'message'=>$success ? "Class deleted." : "Failed to delete."
]);