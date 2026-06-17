<?php 
if (session_status() === PHP_SESSION_NONE) session_start();

// Fetch username and role
$username = '';
$role = '';

if (isset($_SESSION['user_id']) && isset($conn)) {
    $stmt = $conn->prepare("SELECT name, role FROM users WHERE id=?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user) {
        $username = $user['name'];
        $role = $user['role'];
    }
}

// ACTIVE PAGE DETECTION
$currentPage = basename($_SERVER['PHP_SELF']);
$queryString = $_SERVER['QUERY_STRING'] ?? '';

include 'views/nav.html';
?>