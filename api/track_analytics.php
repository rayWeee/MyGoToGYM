<?php
include 'db.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$data = json_decode(file_get_contents("php://input"), true);
$action = $data['action'] ?? 'visit';
$page_path = $data['page_path'] ?? null;

// Only track if the user has accepted cookies
if (isset($_COOKIE['cookie_consent']) && $_COOKIE['cookie_consent'] === 'accepted') {
    $user_id = $_SESSION['user_id'] ?? null;
    
    $stmt = $conn->prepare("INSERT INTO site_analytics (user_id, action, page_path) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $action, $page_path);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'No consent']);
}