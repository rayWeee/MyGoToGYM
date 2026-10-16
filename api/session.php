<?php
/**
 * Shared session bootstrap. Every entry point must use this instead of calling
 * session_start() directly, because XAMPP's configured temp directory is not
 * writable by the local PHP server.
 */
function start_app_session(): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }

    $tempDirectory = getenv('TEMP') ?: sys_get_temp_dir();
    $sessionPath = rtrim($tempDirectory, "\\/") . DIRECTORY_SEPARATOR . 'gym_tracker2_sessions';
    if (!is_dir($sessionPath) && !mkdir($sessionPath, 0770, true) && !is_dir($sessionPath)) {
        error_log('Unable to create session storage directory.');
        http_response_code(500);
        exit('Session storage is unavailable.');
    }

    session_save_path($sessionPath);
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? '') === '443';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}
