<?php
include 'api/db.php';

$message = "";
$success = false;
$emailChangeConfirmed = false;

if (!isset($_GET['token']) || empty($_GET['token'])) {
    $message = $t['invalid_link_message_generic'];
} else {
    $token = $_GET['token'];

    $stmt = $conn->prepare("SELECT id, email, email_verified, pending_email, verification_token, (verification_expires IS NOT NULL AND verification_expires < NOW()) AS token_expired FROM users WHERE verification_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user) {
        if ((int) $user['token_expired'] === 1) {
            $stmt = $conn->prepare("UPDATE users SET pending_email = NULL, verification_token = NULL, verification_expires = NULL WHERE id = ? AND verification_token = ?");
            $stmt->bind_param("is", $user['id'], $token);
            $stmt->execute();
            $message = $t['invalid_or_expired_token'];
        } elseif (!empty($user['email_verified']) && !empty($user['pending_email'])) {
            $newEmail = strtolower(trim($user['pending_email']));
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->bind_param("si", $newEmail, $user['id']);
            $stmt->execute();

            if ($stmt->get_result()->num_rows > 0) {
                $stmt = $conn->prepare("UPDATE users SET pending_email = NULL, verification_token = NULL, verification_expires = NULL WHERE id = ? AND verification_token = ?");
                $stmt->bind_param("is", $user['id'], $token);
                $stmt->execute();
                $message = $t['email_change_taken'];
            } else {
                try {
                    $stmt = $conn->prepare("UPDATE users SET email = ?, pending_email = NULL, verification_token = NULL, verification_expires = NULL WHERE id = ? AND verification_token = ? AND pending_email = ?");
                    $stmt->bind_param("siss", $newEmail, $user['id'], $token, $user['pending_email']);

                    if ($stmt->execute() && $stmt->affected_rows === 1) {
                        $message = $t['email_change_verified'];
                        $success = true;
                        $emailChangeConfirmed = true;
                    } else {
                        $message = $t['invalid_or_expired_token'];
                    }
                } catch (mysqli_sql_exception $e) {
                    $message = $t['email_change_taken'];
                }
            }
        } elseif (empty($user['email_verified'])) {
            $stmt = $conn->prepare("UPDATE users SET email_verified = 1, verification_token = NULL, verification_expires = NULL WHERE id = ? AND verification_token = ?");
            $stmt->bind_param("is", $user['id'], $token);

            if ($stmt->execute() && $stmt->affected_rows === 1) {
                $message = $t['verified_message'];
                $success = true;
            } else {
                $message = $t['invalid_or_expired_token'];
            }
        } else {
            $message = $t['invalid_or_expired_token'];
        }
    } else {
        $message = $t['invalid_or_expired_token'];
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Email Verification</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="assets/css/nav.css" rel="stylesheet">
<link href="assets/css/theme.css" rel="stylesheet">
<link href="assets/css/verify_email.css" rel="stylesheet">
</head>
<body>

    <div class="page-wrapper">
        <?php include 'api/nav.php'; ?>

        <div class="verify-wrapper">
            <div class="verify-card">
                <?php if ($success): ?>
                    <span class="icon">✔️</span>
                    <h2><?= $t['verified_status'] ?></h2>
                    <p><?= htmlspecialchars($message) ?></p>
                    <a href="<?= $basePath ?>/<?= $emailChangeConfirmed ? 'profile' : 'login' ?>" class="btn-verify"><?= $emailChangeConfirmed ? $t['return_to_profile'] : $t['go_to_login'] ?></a>
                <?php else: ?>
                    <span class="icon">❌</span>
                    <h2><?= $t['invalid_link_status'] ?></h2>
                    <p><?= htmlspecialchars($message) ?></p>
                    <div style="display: flex; flex-direction: column; gap: 15px; align-items: center;">
                        <div id="resendSection" style="width: 100%; margin-bottom: 10px;">
                            <input type="email" id="resendEmail" placeholder="<?= $t['email_label'] ?>" style="margin-bottom: 10px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: white; padding: 12px; border-radius: 10px; width: 100%;">
                            <button onclick="resendVerification()" class="btn-verify" style="width: 100%; border: none; cursor: pointer; display: block;">
                                <?= $t['resend_verification'] ?>
                            </button>
                        </div>
                        <a href="<?= $basePath ?>/register" class="btn-verify"><?= $t['try_registering_again'] ?></a>
                        <a href="<?= $basePath ?>/homepage" style="color: rgba(255,255,255,0.4); text-decoration: none; font-size: 0.9rem;"><?= $t['back_to_home'] ?></a>
                    </div>

                    <script>
                    function resendVerification() {
                        const email = document.getElementById('resendEmail').value;
                        if (!email) { alert("Please enter your email address."); return; }

                        fetch('<?= $basePath ?>/api/resend_verification', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ email: email })
                        })
                        .then(r => r.json())
                        .then(data => {
                            alert(data.message);
                            if (data.success) {
                                const section = document.getElementById('resendSection');
                                section.textContent = '';
                                const message = document.createElement('p');
                                message.style.cssText = 'color: #4cff88; font-size: 1rem; margin: 10px 0;';
                                message.textContent = data.message;
                                section.appendChild(message);

                                if (data.verification_link) {
                                    const link = document.createElement('a');
                                    link.href = data.verification_link;
                                    link.className = 'btn-verify';
                                    link.textContent = 'Verify this account locally';
                                    section.appendChild(link);
                                }
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            alert("An unexpected error occurred. Please try again.");
                        });
                    }
                    </script>
                <?php endif; ?>
            </div>
        </div>

        <?php include 'api/footer.php'; ?>
    </div>

</body>
</html>
