<?php
include 'api/db.php';
require_once __DIR__ . '/api/csrf.php';

// Prevent logged-in users from accessing this page
if (isset($_SESSION['user_id'])) {
    http_response_code(403);
    require __DIR__ . '/403.php';
    exit;
}


$token = $_GET['token'] ?? '';
$error = '';
$success = false;

// If token is missing, treat it as an invalid/expired request immediately
if (empty($token)) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

// Verify token
$now = date("Y-m-d H:i:s");
$stmt = $conn->prepare("SELECT email FROM password_resets WHERE token = ? AND expiry > ?");
if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param("ss", $token, $now);
$stmt->execute();
$res = $stmt->get_result();
$resetRequest = $res->fetch_assoc();

if (!$resetRequest) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!check_csrf($_POST['csrf_token'])) {
        $error = "Invalid CSRF token";
    } else {
        $newPass = $_POST['password'];
        $confirmPass = $_POST['confirm_password'];
        if ($newPass !== $confirmPass) {
            $error = $t['password_mismatch'];
        } elseif (strlen($newPass) < 8) {
            $error = $t['password_min_length'];
        } else {
            $hashed = password_hash($newPass, PASSWORD_DEFAULT);
            $email = $resetRequest['email'];

            // Update user password
            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
                $stmt->bind_param("ss", $hashed, $email);
                $stmt->execute();

                // Delete token
                $stmt = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
                $stmt->bind_param("s", $token);
                $stmt->execute();

                $conn->commit();
                $success = true;
            } catch (Exception $e) {
                $conn->rollback();
                $error = "Database error: " . $e->getMessage();
            }
        }
    }
}

$csrf_token = generate_csrf();
include 'views/reset_password.html';