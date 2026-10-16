<?php
require_once __DIR__ . '/../api/db.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/export_helpers.php';

use Dompdf\Dompdf;

if (basename($_SERVER['PHP_SELF'] ?? '') === 'export_pdf.php') {
    header('Location: ' . $basePath . '/export/export_pdf' . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''), true, 301);
    exit;
}
export_require_admin($basePath);

$start = isset($_GET['start_date']) && $_GET['start_date'] !== '' ? export_date($_GET['start_date']) : null;
$end = isset($_GET['end_date']) && $_GET['end_date'] !== '' ? export_date($_GET['end_date']) : null;
if ((isset($_GET['start_date']) && $_GET['start_date'] !== '' && !$start) || (isset($_GET['end_date']) && $_GET['end_date'] !== '' && !$end) || ($start && $end && $start > $end)) {
    http_response_code(422); exit('Invalid date range.');
}
$sql = 'SELECT c.title,c.description,c.start_datetime,c.capacity,COUNT(r.id) AS booked FROM classes c LEFT JOIN reservations r ON c.id=r.class_id';
$where = []; $params = []; $types = '';
if ($start) { $where[] = 'c.start_datetime >= ?'; $params[] = $start . ' 00:00:00'; $types .= 's'; }
if ($end) { $where[] = 'c.start_datetime <= ?'; $params[] = $end . ' 23:59:59'; $types .= 's'; }
if ($where) { $sql .= ' WHERE ' . implode(' AND ', $where); }
$sql .= ' GROUP BY c.id ORDER BY c.start_datetime';
$stmt = $conn->prepare($sql); if ($params) { $stmt->bind_param($types, ...$params); } $stmt->execute(); $result = $stmt->get_result();
$rows = ''; $count = 0;
while ($row = $result->fetch_assoc()) {
    $count++; $capacity = (int)$row['capacity']; $booked = (int)$row['booked'];
    $rows .= '<tr><td><strong>' . export_html($row['title']) . '</strong></td><td>' . export_html($row['description']) . '</td><td>' . date('d.m.Y H:i', strtotime($row['start_datetime'])) . '</td><td class="center">' . $booked . ' / ' . $capacity . '<br><small>' . ($capacity ? round($booked / $capacity * 100) : 0) . '% filled</small></td></tr>';
}
if (!$count) { $rows = '<tr><td colspan="4" class="empty">No workouts found for this period.</td></tr>'; }
$period = ($start ?: 'All time') . ' - ' . ($end ?: 'Today');
$logo = 'data:image/jpeg;base64,' . base64_encode((string) file_get_contents(__DIR__ . '/../assets/images/logo2-pdf.jpg'));
$html = '<!doctype html><html><head><meta charset="utf-8"><style>@page{margin:20mm 14mm}body{font-family:DejaVu Sans;color:#1f2937;font-size:10px}.logo{float:right;width:35mm;height:auto;margin:0 0 4mm 10mm}.brand{color:#2563eb;font-weight:bold;letter-spacing:.8px}h1{font-size:22px;margin:7px 0}.meta{color:#64748b;margin:0 0 18px}table{width:100%;border-collapse:collapse}th{background:#1d4ed8;color:#fff;text-align:left;padding:9px}td{padding:9px;border-bottom:1px solid #dbe3ef;vertical-align:top}tr:nth-child(even)td{background:#f8fafc}.center{text-align:center}.empty{text-align:center;padding:25px;color:#64748b}small{color:#64748b}</style></head><body><img class="logo" src="' . $logo . '"><div class="brand">MYGOTOGYM</div><h1>Workout schedule report</h1><p class="meta">Period: ' . export_html($period) . '<br>Generated: ' . date('d.m.Y H:i') . '</p><table><thead><tr><th>Workout</th><th>Description</th><th>Date and time</th><th>Attendance</th></tr></thead><tbody>' . $rows . '</tbody></table></body></html>';
$pdf = new Dompdf(); $pdf->setPaper('A4', 'landscape'); $pdf->loadHtml($html, 'UTF-8'); $pdf->render(); $pdf->stream('gym_schedule_report_' . date('Y-m-d') . '.pdf', ['Attachment' => true]);
