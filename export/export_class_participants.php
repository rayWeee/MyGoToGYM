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

// Fetch class title for filename
$stmt = $conn->prepare("SELECT title FROM classes WHERE id = ?");
$stmt->bind_param("i", $class_id);
$stmt->execute();
$class = $stmt->get_result()->fetch_assoc();
$title = $class['title'] ?? 'workout';
$filename = str_replace(' ', '_', $title) . "_participants_" . date('Y-m-d') . ".csv";

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

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
fputcsv($output, ['Name', 'Email Address', 'Phone Number']);

while ($row = $result->fetch_assoc()) {
    fputcsv($output, [$row['name'], $row['email'], $row['phone']]);
}
fclose($output);