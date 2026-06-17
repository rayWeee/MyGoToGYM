<?php
/**
 * Secure Configuration
 * Protected from URL access by router.php
 */

define('STRIPE_SECRET_KEY', 'sk_test_REPLACE_WITH_YOUR_ACTUAL_SECRET');

// PHPMailer SMTP Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USERNAME', 'raivislo03@gmail.com'); // Replace with your actual Gmail address
define('SMTP_PASSWORD', 'lzsn rbnw qwte xzxd'); // Your generated App Password

// You get this from the Stripe Dashboard (Webhooks section) or Stripe CLI
define('STRIPE_WEBHOOK_SECRET', 'whsec_REPLACE_WITH_YOUR_ACTUAL_WEBHOOK_SECRET');