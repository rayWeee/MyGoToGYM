<?php 
require_once __DIR__ . '/../api/db.php';
require_once __DIR__ . '/../api/csrf.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . $basePath . "/login");
    exit;
}

// get current user role
$stmt = $conn->prepare("SELECT name, role FROM users WHERE id=?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

$username = $result['name'] ?? '';
$role = $result['role'] ?? 'user';
$_SESSION['role'] = $role;

if ($role !== 'admin') {
    http_response_code(403);
    require __DIR__ . '/../403.php';
    exit;
}

$csrf_token = generate_csrf();

/**
 * Helper function to get analytics counts
 */
function getCount($conn, $action, $period, $isGuest = false) {
    $sql = "SELECT COUNT(*) as total FROM site_analytics WHERE action = ?";
    if ($isGuest) $sql .= " AND user_id IS NULL";
    else if ($action === 'login') $sql .= " AND user_id IS NOT NULL";

    if ($period === 'today') $sql .= " AND DATE(created_at) = CURDATE()";
    elseif ($period === 'month') $sql .= " AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
    elseif ($period === 'year') $sql .= " AND YEAR(created_at) = YEAR(CURDATE())";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $action);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc()['total'];
}

// Analytics: Top pages
$top_pages = $conn->query("SELECT page_path, COUNT(*) as count FROM site_analytics WHERE action='visit' GROUP BY page_path ORDER BY count DESC LIMIT 5");

// Users: Pagination Logic
$limit = 10; 
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$total_res = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc();
$total_users = $total_res['total'];
$total_pages = ceil($total_users / $limit);

$stmt = $conn->prepare("SELECT id, name, email, role, membership_type FROM users LIMIT ? OFFSET ?");
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$users = $stmt->get_result();

include __DIR__ . '/../views/dashboard.html';
