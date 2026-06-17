<?php
require_once __DIR__ . '/../api/db.php';

if (basename($_SERVER['PHP_SELF'] ?? '') === 'export_pdf.php') {
    $query = $_SERVER['QUERY_STRING'] ?? '';
    header("Location: " . $basePath . "/export/export_pdf" . ($query ? '?' . $query : ''), true, 301);
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

$sql = "
SELECT c.id, c.title, c.description, c.start_datetime, c.capacity, COUNT(r.id) AS booked
FROM classes c
LEFT JOIN reservations r ON c.id = r.class_id
";

$where_clauses = [];
$params = [];
$types = "";

$filter_date_range = '';

if (isset($_GET['start_date']) && !empty($_GET['start_date'])) {
    $where_clauses[] = "c.start_datetime >= ?";
    $params[] = $_GET['start_date'] . ' 00:00:00';
    $types .= "s";
    $filter_date_range .= 'From: ' . htmlspecialchars($_GET['start_date']);
}
if (isset($_GET['end_date']) && !empty($_GET['end_date'])) {
    $where_clauses[] = "c.start_datetime <= ?";
    $params[] = $_GET['end_date'] . ' 23:59:59';
    $types .= "s";
    if (!empty($filter_date_range)) $filter_date_range .= ' ';
    $filter_date_range .= 'To: ' . htmlspecialchars($_GET['end_date']);
}

if (!empty($where_clauses)) $sql .= " WHERE " . implode(" AND ", $where_clauses);
$sql .= " GROUP BY c.id ORDER BY c.start_datetime ASC";
$stmt = $conn->prepare($sql);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

include __DIR__ . '/../views/export_pdf.html';
