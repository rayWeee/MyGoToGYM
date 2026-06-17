<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

include 'api/db.php';

// If accessed directly with .php extension, redirect to clean URL
if (basename($_SERVER['PHP_SELF']) === 'register.php') {
    header("Location: " . $basePath . "/register", true, 301);
    exit;
}
require_once 'api/Mailer.php';

if (isset($_SESSION['user_id'])) {
    header("Location: " . $basePath . "/index");
    exit;
}

require_once __DIR__ . '/api/csrf.php';

$error = '';
$name = '';
$email = '';

if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!check_csrf($_POST['csrf_token'])){
        $error = "Invalid CSRF token.";
    } else {
        $name = trim($_POST['name']);
        $email = strtolower(trim($_POST['email']));
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'] ?? '';

        if(!$name || !$email || !$password || !$confirm_password){
            $error = "All fields are required.";
        } elseif(!filter_var($email,FILTER_VALIDATE_EMAIL)){
            $error = "Invalid email address.";
        } elseif($password !== $confirm_password){
            $error = "Passwords do not match.";
        } elseif(!preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{6,}$/',$password)){
            $error = "Password must be 6+ chars, 1 uppercase, 1 number, 1 symbol.";
        } else {
            $stmt = $conn->prepare("SELECT id FROM users WHERE email=?");
            $stmt->bind_param("s",$email);
            $stmt->execute();

            if($stmt->get_result()->num_rows>0){
                $error = "Email already registered.";
            } else {
                $hashed = password_hash($password,PASSWORD_DEFAULT);
                $token = bin2hex(random_bytes(32));

                $stmt = $conn->prepare("INSERT INTO users (name,email,password,role,email_verified,verification_token) VALUES (?,?,?,'user',0,?)"); // Set email_verified to 0
                $stmt->bind_param("ssss",$name,$email,$hashed,$token);

                if($stmt->execute()){
                    // Dynamically generate the verification link using $basePath for clean URLs
                    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                    $host = $_SERVER['HTTP_HOST'];
                    $verify_link = "$protocol://$host" . $basePath . "/verfy_email?token=$token&lang=$current_lang"; 

                    if (\App\Mailer::sendVerificationEmail($email, $name, $verify_link, $current_lang)) {
                        header("Location: " . $basePath . "/login?registered=1");
                        exit;
                    } else {
                        $error = "Registration successful, but verification email failed to send.";
                        $showResend = true;
                        $resendEmail = $email;
                    }
                } else {
                    $error = "Registration failed, try again.";
                }
            }
        }
    }
}

$csrf_token = generate_csrf();

include 'views/register.html';