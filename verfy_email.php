<?php
include 'api/db.php';

$message = "";
$success = false;

if (!isset($_GET['token']) || empty($_GET['token'])) {
    $message = $t['invalid_link_message_generic'];
} else {
    $token = $_GET['token'];

    // Find user with this token
    $stmt = $conn->prepare("SELECT id, email_verified FROM users WHERE verification_token = ? AND email_verified = 0"); // Only look for unverified accounts
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        // User found and is unverified
        if ($user['email_verified'] == 1) {
            $message = $t['verified_message']; // Use translated message
            $success = true;
        } else {
            // Mark email as verified & remove token
            $stmt2 = $conn->prepare("UPDATE users SET email_verified = 1, verification_token = NULL WHERE id = ?");
            $stmt2->bind_param("i", $user['id']);

            if ($stmt2->execute()) {
                $message = $t['verified_message']; // Use translated message
                $success = true;
            } else {
                $message = $t['verification_failed']; // Assuming you'll add this to lang.php
            }
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
                    <a href="<?= $basePath ?>/login" class="btn-verify"><?= $t['go_to_login'] ?></a>
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
                                document.getElementById('resendSection').innerHTML = `<p style="color: #4cff88; font-size: 1rem; margin: 10px 0;">${data.message}</p>`;
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