<?php
require_once __DIR__ . '/../api/db.php';
require_once __DIR__ . '/../api/csrf.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/export_helpers.php';

use Dompdf\Dompdf;

export_require_admin($basePath);

if (!isset($_GET['class_id']) || !ctype_digit((string) $_GET['class_id'])) {
    http_response_code(422);
    exit('Class ID is required.');
}

$classId = (int) $_GET['class_id'];
$classStmt = $conn->prepare('SELECT title, start_datetime FROM classes WHERE id = ?');
$classStmt->bind_param('i', $classId);
$classStmt->execute();
$class = $classStmt->get_result()->fetch_assoc();
if (!$class) {
    http_response_code(404);
    exit('Class not found.');
}

$participants = $conn->prepare("SELECT u.name, u.email, u.phone FROM users u JOIN reservations r ON u.id = r.user_id WHERE r.class_id = ? AND r.status = 'reserved' ORDER BY u.name ASC");
$participants->bind_param('i', $classId);
$participants->execute();
$result = $participants->get_result();

$rows = '';
$number = 1;
while ($participant = $result->fetch_assoc()) {
    $rows .= '<tr><td class="number">' . $number++ . '</td><td>' . export_html($participant['name']) . '</td><td>' . export_html($participant['email']) . '</td><td>' . export_html($participant['phone'] ?: 'Not provided') . '</td></tr>';
}
if ($number === 1) {
    $rows = '<tr><td colspan="4" class="empty">No participants have joined this class yet.</td></tr>';
}

$logo = 'data:image/jpeg;base64,' . base64_encode((string) file_get_contents(__DIR__ . '/../assets/images/logo2-pdf.jpg'));
$html = '<!doctype html><html><head><meta charset="utf-8"><style>
@page{margin:20mm 14mm}body{font-family:DejaVu Sans;color:#1f2937;font-size:10px}.logo{float:right;width:35mm;height:auto;margin:0 0 4mm 10mm}.brand{color:#2563eb;font-weight:bold;letter-spacing:.8px}h1{font-size:22px;margin:7px 0}.meta{color:#64748b;margin:0 0 18px}table{width:100%;border-collapse:collapse}th{background:#1d4ed8;color:#fff;text-align:left;padding:9px}td{padding:9px;border-bottom:1px solid #dbe3ef}tr:nth-child(even)td{background:#f8fafc}.number{text-align:center;width:8%}.empty{text-align:center;padding:25px;color:#64748b}
</style></head><body><img class="logo" src="' . $logo . '"><div class="brand">MYGOTOGYM</div><h1>Participant list</h1><p class="meta"><strong>Class:</strong> ' . export_html($class['title']) . '<br><strong>Date:</strong> ' . date('d.m.Y H:i', strtotime($class['start_datetime'])) . '<br><strong>Generated:</strong> ' . date('d.m.Y H:i') . '</p><table><thead><tr><th>No.</th><th>Name</th><th>Email address</th><th>Phone number</th></tr></thead><tbody>' . $rows . '</tbody></table></body></html>';

$pdf = new Dompdf();
$pdf->setPaper('A4');
$pdf->loadHtml($html, 'UTF-8');
$pdf->render();
$filename = preg_replace('/[^a-z0-9_-]+/i', '_', $class['title']) . '_participants_' . date('Y-m-d') . '.pdf';
$pdf->stream($filename, ['Attachment' => true]);
