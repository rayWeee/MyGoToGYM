<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../api/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$sql = "SELECT id, name, email, role, membership_type FROM users";
$countSql = "SELECT COUNT(*) as total FROM users";
$params = [];
$types = "";

if ($query !== '') {
    $where = " WHERE name LIKE ? OR email LIKE ?";
    $sql .= $where;
    $countSql .= $where;
    $searchTerm = "%$query%";
    $params = [$searchTerm, $searchTerm];
    $types = "ss";
}

// Get total for pagination
$stmtCount = $conn->prepare($countSql);
if ($query !== '') {
    $stmtCount->bind_param($types, ...$params);
}
$stmtCount->execute();
$totalUsers = $stmtCount->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalUsers / $limit);

// Get users
$sql .= " LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$limitParam = $limit;
$offsetParam = $offset;
$allParams = array_merge($params, [$limitParam, $offsetParam]);
$stmt->bind_param($types . "ii", ...$allParams);
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    'success' => true,
    'users' => $users,
    'totalPages' => $totalPages,
    'currentPage' => $page
]);
