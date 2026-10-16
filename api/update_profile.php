<?php
header('Content-Type: application/json');
include 'db.php';
require_once 'csrf.php';
require_once __DIR__ . '/../vendor/autoload.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !is_array($data) || !check_csrf($data['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

$user_id = $_SESSION['user_id'];

if ($action === 'info') {
    if (array_key_exists('name', $data)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Name cannot be changed from profile settings']);
        exit;
    }

    $email = strtolower(trim($data['email'] ?? ''));
    $phone = trim($data['phone'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
        echo json_encode(['success' => false, 'message' => 'Invalid email']);
        exit;
    }

    if (strlen($phone) > 100) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => $t['invalid_phone']]);
        exit;
    }

    $phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
    if ($phone !== '') {
        if ($phone[0] !== '+') {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => $t['invalid_phone']]);
            exit;
        }

        try {
            $parsedPhone = $phoneUtil->parse($phone, null);
            if (!$phoneUtil->isValidNumber($parsedPhone)) {
                throw new \libphonenumber\NumberParseException(0, 'Invalid phone number');
            }
            $phone = $phoneUtil->format($parsedPhone, \libphonenumber\PhoneNumberFormat::E164);
        } catch (\libphonenumber\NumberParseException $e) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => $t['invalid_phone']]);
            exit;
        }
    }

    $stmt = $conn->prepare("SELECT name, email, email_verified, phone FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $profile = $stmt->get_result()->fetch_assoc();
    if (!$profile || empty($profile['email_verified'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Verified account required']);
        exit;
    }

    $currentEmail = strtolower($profile['email']);
    $currentPhone = trim((string) ($profile['phone'] ?? ''));
    if ($currentPhone !== '' && $currentPhone[0] === '+') {
        try {
            $currentParsedPhone = $phoneUtil->parse($currentPhone, null);
            if ($phoneUtil->isValidNumber($currentParsedPhone)) {
                $currentPhone = $phoneUtil->format($currentParsedPhone, \libphonenumber\PhoneNumberFormat::E164);
            }
        } catch (\libphonenumber\NumberParseException $e) {
            // Leave legacy phone values unchanged if they cannot be parsed.
        }
    }
    $phoneChanged = $phone !== $currentPhone;
    if ($email !== $currentEmail) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => $t['email_already_in_use']]);
            exit;
        }
    }

    if ($email === $currentEmail) {
        if (!$phoneChanged) {
            echo json_encode(['success' => true, 'message' => '']);
            exit;
        }

        $stmt = $conn->prepare("UPDATE users SET phone = ? WHERE id = ?");
        $stmt->bind_param("si", $phone, $user_id);
        echo json_encode($stmt->execute()
            ? ['success' => true, 'message' => $t['phone_updated']]
            : ['success' => false, 'message' => 'Update failed']);
        exit;
    }

    $verificationToken = bin2hex(random_bytes(32));
    if ($phoneChanged) {
        $stmt = $conn->prepare("UPDATE users SET phone = ?, pending_email = ?, verification_token = ?, verification_expires = DATE_ADD(NOW(), INTERVAL 1 DAY) WHERE id = ?");
        $stmt->bind_param("sssi", $phone, $email, $verificationToken, $user_id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET pending_email = ?, verification_token = ?, verification_expires = DATE_ADD(NOW(), INTERVAL 1 DAY) WHERE id = ?");
        $stmt->bind_param("ssi", $email, $verificationToken, $user_id);
    }
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Update failed']);
        exit;
    }

    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $verificationLink = $protocol . '://' . $host . $basePath . '/verfy_email?token=' . urlencode($verificationToken) . '&lang=' . urlencode($current_lang);

    require_once __DIR__ . '/../vendor/autoload.php';
    require_once __DIR__ . '/Mailer.php';
    if (\App\Mailer::sendEmailChangeVerification($email, $profile['name'], $verificationLink, $current_lang)) {
        $message = $phoneChanged ? $t['phone_and_email_change_confirmation_sent'] : $t['email_change_confirmation_sent'];
        echo json_encode(['success' => true, 'message' => $message]);
    } elseif (LOCAL_VERIFICATION_FALLBACK) {
        echo json_encode([
            'success' => true,
            'message' => $phoneChanged ? $t['phone_updated_email_change_confirmation_failed'] : $t['email_change_confirmation_failed'],
            'verification_link' => $verificationLink,
            'verification_link_label' => $t['local_email_verification_link']
        ]);
    } else {
        $stmt = $conn->prepare("UPDATE users SET pending_email = NULL, verification_token = NULL, verification_expires = NULL WHERE id = ? AND verification_token = ?");
        $stmt->bind_param("is", $user_id, $verificationToken);
        $stmt->execute();
        $message = $phoneChanged ? $t['phone_updated_email_change_confirmation_failed'] : $t['email_change_confirmation_failed'];
        echo json_encode(['success' => false, 'message' => $message]);
    }

} elseif ($action === 'password') {
    $current = $data['current_password'] ?? '';
    $new = $data['new_password'] ?? '';

    if (!preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $new)) {
        echo json_encode(['success' => false, 'message' => 'New password does not meet requirements.']);
        exit;
    }

    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!password_verify($current, $user['password'])) {
        echo json_encode(['success' => false, 'message' => 'Current password incorrect']);
        exit;
    }

    $hashed = password_hash($new, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hashed, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Password update failed']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
