<?php
include 'api/db.php';
require_once __DIR__ . '/api/csrf.php';

// Prevent logged-in users from accessing this page
if (isset($_SESSION['user_id'])) {
    http_response_code(403);
    require __DIR__ . '/403.php';
    exit;
}

require_once 'api/Mailer.php';

require 'vendor/autoload.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!check_csrf($_POST['csrf_token'])) {
        $error = "Invalid CSRF token";
    } else {
        $email = strtolower(trim($_POST['email']));

        // Check if user exists and is verified
        $stmt = $conn->prepare("SELECT id, name FROM users WHERE email = ? AND email_verified = 1");
        if (!$stmt) {
            die("Database error (SELECT): " . $conn->error);
        }
        
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();
        $user = $res->fetch_assoc();

        if ($user) {
            // Generate secure token
            $token = bin2hex(random_bytes(32));
            $expiry = date("Y-m-d H:i:s", strtotime("+1 hour"));

            $stmt = $conn->prepare("REPLACE INTO password_resets (email, token, expiry) VALUES (?, ?, ?)");
            if (!$stmt) {
                die("Database error (REPLACE): " . $conn->error);
            }

            $stmt->bind_param("sss", $email, $token, $expiry);
            $stmt->execute();

            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
            $resetLink = "$protocol://" . $_SERVER['HTTP_HOST'] . $basePath . "/reset_password?token=" . urlencode($token);

            \App\Mailer::sendResetEmail($email, $user['name'], $resetLink, $current_lang);
        }
        
        // Always show success message for security
        $message = $t['reset_link_sent'];
    }
}

$csrf_token = generate_csrf();

include 'views/forgot_password.html';