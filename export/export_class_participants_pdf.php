<?php
require_once __DIR__ . '/../api/db.php';

// Security check: Admin only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Access denied.");
}

if (!isset($_GET['class_id'])) {
    die("Class ID missing.");
}

$class_id = intval($_GET['class_id']);

// Fetch class info
$stmt = $conn->prepare("SELECT title, start_datetime FROM classes WHERE id = ?");
$stmt->bind_param("i", $class_id);
$stmt->execute();
$class = $stmt->get_result()->fetch_assoc();

if (!$class) die("Class not found.");

// Fetch participants
$stmt = $conn->prepare("
    SELECT u.name, u.email, u.phone 
    FROM users u
    JOIN reservations r ON u.id = r.user_id
    WHERE r.class_id = ? AND r.status = 'reserved'
");
$stmt->bind_param("i", $class_id);
$stmt->execute();
$result = $stmt->get_result();

include __DIR__ . '/../views/export_class_participants_pdf.html';