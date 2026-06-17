<?php
include 'api/db.php';

// If accessed directly with .php extension, redirect to clean URL
if (basename($_SERVER['PHP_SELF']) === 'login.php') {
    header("Location: " . $basePath . "/login", true, 301);
    exit;
}

if (isset($_SESSION['user_id'])) {
    header("Location: " . $basePath . "/homepage");
    exit;
}

require_once __DIR__ . '/api/csrf.php';

$error = '';

if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!check_csrf($_POST['csrf_token'])){
        $error = "Invalid CSRF token";
    } else {
        $email = strtolower(trim($_POST['email']));
        $password = $_POST['password'];

        $stmt = $conn->prepare("SELECT id,password,role,failed_logins,lock_until,email_verified FROM users WHERE email=?");
        $stmt->bind_param("s",$email);
        $stmt->execute();
        $u = $stmt->get_result()->fetch_assoc();

        if($u){
            if($u['lock_until'] && strtotime($u['lock_until'])>time()){
                $error = "Account locked. Try later.";
            } elseif(!password_verify($password,$u['password'])){
                $failed = $u['failed_logins']+1;
                $lock = $failed>=5 ? date('Y-m-d H:i:s',strtotime('+15 minutes')) : NULL;
                $stmt2 = $conn->prepare("UPDATE users SET failed_logins=?, lock_until=? WHERE id=?"); // This uses $conn, consistent with db.php
                $stmt2->bind_param("isi",$failed,$lock,$u['id']);
                $stmt2->execute();
                $error = "Invalid email or password.";
            } elseif(!$u['email_verified']){
                $error = "Please verify your email first.";
                $showResend = true;
                $resendEmail = $email;
            } else {
                $stmt2 = $conn->prepare("UPDATE users SET failed_logins=0, lock_until=NULL WHERE id=?");
                $stmt2->bind_param("i",$u['id']);
                $stmt2->execute();

                session_regenerate_id(true);
                $_SESSION['user_id'] = $u['id'];
                $_SESSION['role'] = $u['role'];

                // Track login if consent is given
                if (isset($_COOKIE['cookie_consent']) && $_COOKIE['cookie_consent'] === 'accepted') {
                    $track_stmt = $conn->prepare("INSERT INTO site_analytics (user_id, action) VALUES (?, 'login')");
                    $track_stmt->bind_param("i", $u['id']);
                    $track_stmt->execute();
                }

                header("Location:" . $basePath . "/homepage");
                exit;
            }
        } else {
            $error = "Invalid email or password.";
        }
    }
}

$csrf_token = generate_csrf();

include 'views/login.html';