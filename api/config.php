<?php
/**
 * Application configuration.
 * Sensitive values should be placed in a local .env file and never committed to source control.
 */

function app_env(string $key, $default = null) {
    static $loaded = false;
    static $env = [];

    if (!$loaded) {
        // A project-local .env always takes precedence. For this local XAMPP
        // setup, the shared htdocs .env can provide existing SMTP settings.
        $sharedEnvFile = dirname(dirname(__DIR__)) . '/.env';
        $envFiles = [$sharedEnvFile, dirname(__DIR__) . '/.env'];
        foreach ($envFiles as $envFile) {
            if (!is_file($envFile)) {
                continue;
            }

            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (!$lines) {
                continue;
            }

            foreach ($lines as $line) {
                $trimmed = trim($line);
                if ($trimmed === '' || strpos($trimmed, '#') === 0) {
                    continue;
                }

                $parts = explode('=', $trimmed, 2);
                if (count($parts) !== 2) {
                    continue;
                }

                $name = trim($parts[0]);
                $value = trim($parts[1]);

                // The shared XAMPP .env belongs to another local project.
                // Only reuse its explicitly named email settings; never import
                // its database credentials into Gym Tracker.
                if ($envFile === $sharedEnvFile && !in_array($name, [
                    'BOOKING_EMAIL',
                    'BOOKING_SMTP_HOST',
                    'BOOKING_SMTP_PORT',
                    'BOOKING_SMTP_USER',
                    'BOOKING_SMTP_PASSWORD'
                ], true)) {
                    continue;
                }
                $env[$name] = trim($value, " \t\n\r\0\x0B\"'");
            }
        }
        $loaded = true;
    }

    if (array_key_exists($key, $env)) {
        return $env[$key];
    }

    $value = getenv($key);
    if ($value !== false) {
        return $value;
    }

    return $default;
}

function set_app_security_headers(): void {
    if (headers_sent()) {
        return;
    }

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://fonts.gstatic.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; img-src 'self' data: https:; font-src 'self' https://fonts.gstatic.com https://fonts.googleapis.com data:; connect-src 'self'; frame-src https://maps.google.com https://www.google.com; object-src 'none'; base-uri 'self'; frame-ancestors 'self';");

    if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? '') == '443') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

define('APP_ENV', app_env('APP_ENV', 'development'));
define('DB_HOST', app_env('DB_HOST', 'localhost'));
define('DB_NAME', app_env('DB_NAME', 'gym_tracker2'));
define('DB_USER', app_env('DB_USER', 'root'));
define('DB_PASS', app_env('DB_PASS', ''));
define('DB_PORT', (int) app_env('DB_PORT', 3306));

define('STRIPE_SECRET_KEY', app_env('STRIPE_SECRET_KEY', 'sk_test_replace_with_your_key'));
define('SMTP_HOST', app_env('SMTP_HOST', app_env('BOOKING_SMTP_HOST', 'smtp.gmail.com')));
define('SMTP_PORT', (int) app_env('SMTP_PORT', app_env('BOOKING_SMTP_PORT', 587)));
define('SMTP_USERNAME', app_env('SMTP_USERNAME', app_env('BOOKING_SMTP_USER', app_env('BOOKING_EMAIL', 'your_email@gmail.com'))));
define('SMTP_PASSWORD', app_env('SMTP_PASSWORD', app_env('BOOKING_SMTP_PASSWORD', 'replace_with_app_password')));
define('LOCAL_VERIFICATION_FALLBACK', filter_var(app_env('LOCAL_VERIFICATION_FALLBACK', false), FILTER_VALIDATE_BOOLEAN));
define('STRIPE_WEBHOOK_SECRET', app_env('STRIPE_WEBHOOK_SECRET', 'whsec_replace_with_your_webhook_secret'));
