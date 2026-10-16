<?php

function export_require_admin(string $basePath): void {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . $basePath . '/login');
        exit;
    }

    if (($_SESSION['role'] ?? '') !== 'admin') {
        http_response_code(403);
        require __DIR__ . '/../403.php';
        exit;
    }
}

function export_date(string $value): ?string {
    $date = DateTime::createFromFormat('!Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value ? $value : null;
}

function export_safe_cell(?string $value): string {
    $value = trim((string) $value);
    // Spreadsheet programs treat these first characters as formulas. Prefixing
    // them prevents a database value from becoming an executable formula.
    return preg_match('/^[=+\-@]/', $value) ? "'" . $value : $value;
}

function export_html(?string $value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
