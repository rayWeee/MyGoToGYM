<?php
require_once __DIR__ . '/../api/db.php';

if (basename($_SERVER['PHP_SELF'] ?? '') === 'export_excel.php') {
    $query = $_SERVER['QUERY_STRING'] ?? '';
    header("Location: " . $basePath . "/export/export_excel" . ($query ? '?' . $query : ''), true, 301);
    exit;
}

// Security check: Admin only
if (!isset($_SESSION['user_id'])) {
    header("Location: " . $basePath . "/login");
    exit;
}

if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    require __DIR__ . '/../403.php';
    exit;
}

// Fetch class data with booking counts
$sql = "
SELECT 
  c.id,
  c.title,
  c.description,
  c.start_datetime,
  c.capacity,
  COUNT(r.id) AS booked
FROM classes c
LEFT JOIN reservations r ON c.id = r.class_id
";

$where_clauses = [];
$params = [];
$types = "";

if (isset($_GET['start_date']) && !empty($_GET['start_date'])) {
    $where_clauses[] = "c.start_datetime >= ?";
    $params[] = $_GET['start_date'] . ' 00:00:00';
    $types .= "s";
}
if (isset($_GET['end_date']) && !empty($_GET['end_date'])) {
    $where_clauses[] = "c.start_datetime <= ?";
    $params[] = $_GET['end_date'] . ' 23:59:59';
    $types .= "s";
}

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}

$sql .= " GROUP BY c.id ORDER BY c.start_datetime ASC";
$stmt = $conn->prepare($sql);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Set headers for download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=gym_schedule_export_' . date('Y-m-d') . '.csv');

// Open output stream
$output = fopen('php://output', 'w');
// Add UTF-8 BOM for Excel compatibility with special characters
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Column headers
fputcsv($output, ['ID', 'Workout Title', 'Description', 'Date & Time', 'Max Capacity', 'Participants Joined']);

while ($row = $result->fetch_assoc()) {
    fputcsv($output, [$row['id'], $row['title'], $row['description'], $row['start_datetime'], $row['capacity'], $row['booked']]);
}
fclose($output);
