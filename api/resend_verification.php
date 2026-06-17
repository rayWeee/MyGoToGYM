<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';
header('Content-Type: application/json');
require_once 'config.php'; // Load SMTP constants
require 'db.php';
require_once __DIR__ . '/Mailer.php';

$data = json_decode(file_get_contents("php://input"), true);
$email = strtolower(trim($data['email'] ?? ''));

if (!$email) {
    echo json_encode(['success' => false, 'message' => 'Email is required.']);
    exit;
}

$stmt = $conn->prepare("SELECT id, name, verification_token, email_verified FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'User not found.']);
    exit;
}

if ($user['email_verified']) {
    echo json_encode(['success' => false, 'message' => 'Email is already verified.']);
    exit;
}

$name = $user['name'];
$token = $user['verification_token'];

// Generate new token if missing for any reason
if (!$token) {
    $token = bin2hex(random_bytes(32));
    $upd = $conn->prepare("UPDATE users SET verification_token = ? WHERE id = ?");
    $upd->bind_param("si", $token, $user['id']);
    $upd->execute();
}

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$verify_link = "$protocol://$host" . $basePath . "/verfy_email?token=$token&lang=$current_lang"; // Use $basePath and clean URL

try {
    // Use the centralized Mailer class to send the email
    if (\App\Mailer::sendVerificationEmail($email, $name, $verify_link, $current_lang)) {
        echo json_encode(['success' => true, 'message' => 'Verification email resent successfully!']);
    } else {
        // Mailer::sendVerificationEmail logs its own errors, so just return a generic failure message
        echo json_encode(['success' => false, 'message' => 'Failed to resend verification email.']);
    }

} catch (Exception $e) {
    // This catch block is a fallback in case Mailer::sendVerificationEmail doesn't catch its own exceptions
    error_log("Resend Mailer Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => "An unexpected error occurred while sending the email."]);
}