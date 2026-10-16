<?php
require_once __DIR__ . '/../api/db.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/export_helpers.php';

use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Writer\XLSX\Options;

if (basename($_SERVER['PHP_SELF'] ?? '') === 'export_excel.php') {
    $query = $_SERVER['QUERY_STRING'] ?? '';
    header("Location: " . $basePath . "/export/export_excel" . ($query ? '?' . $query : ''), true, 301);
    exit;
}

export_require_admin($basePath);

// Fetch class data with booking counts
$sql = "
SELECT 
  c.id,
  c.title,
  c.description,
  c.start_datetime,
  c.capacity,
  COUNT(r.id) AS booked
FROM classes c
LEFT JOIN reservations r ON c.id = r.class_id
";

$where_clauses = [];
$params = [];
$types = "";

if (isset($_GET['start_date']) && $_GET['start_date'] !== '') {
    $startDate = export_date($_GET['start_date']);
    if (!$startDate) {
        http_response_code(422);
        exit('Invalid start date.');
    }
    $where_clauses[] = "c.start_datetime >= ?";
    $params[] = $startDate . ' 00:00:00';
    $types .= "s";
}
if (isset($_GET['end_date']) && $_GET['end_date'] !== '') {
    $endDate = export_date($_GET['end_date']);
    if (!$endDate) {
        http_response_code(422);
        exit('Invalid end date.');
    }
    $where_clauses[] = "c.start_datetime <= ?";
    $params[] = $endDate . ' 23:59:59';
    $types .= "s";
}
if (isset($startDate, $endDate) && $startDate > $endDate) {
    http_response_code(422);
    exit('Start date must be before end date.');
}

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}

$sql .= " GROUP BY c.id ORDER BY c.start_datetime ASC";
$stmt = $conn->prepare($sql);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$options = new Options();
$options->DEFAULT_ROW_STYLE->setFontSize(9);
$options->DEFAULT_ROW_HEIGHT = 14;
$options->setColumnWidth(8, 1, 5, 6, 7, 8);
$options->setColumnWidth(22, 2);
$options->setColumnWidth(38, 3);
$options->setColumnWidth(18, 4);
$writer = new Writer($options);
$writer->openToBrowser('gym_schedule_report_' . date('Y-m-d') . '.xlsx');

$titleStyle = (new Style())->setFontBold()->setFontSize(14)->setFontColor(Color::WHITE)->setBackgroundColor(Color::DARK_BLUE);
$metaStyle = (new Style())->setFontSize(10);
$headerStyle = (new Style())->setFontBold()->setFontSize(9)->setFontColor(Color::WHITE)->setBackgroundColor(Color::BLUE);
$writer->addRow(Row::fromValues(['MyGoToGym Schedule Report'], $titleStyle));
$writer->addRow(Row::fromValues(['Generated', date('Y-m-d H:i'), 'Period', trim(($startDate ?? 'All time') . ' - ' . ($endDate ?? 'Today'))], $metaStyle));
$writer->addRow(Row::fromValues([]));
$writer->addRow(Row::fromValues([]));
$writer->addRow(Row::fromValues(['ID', 'Workout title', 'Description', 'Date and time', 'Capacity', 'Booked', 'Available', 'Attendance'], $headerStyle));

while ($row = $result->fetch_assoc()) {
    $capacity = (int) $row['capacity'];
    $booked = (int) $row['booked'];
    $attendance = $capacity > 0 ? round(($booked / $capacity) * 100, 1) . '%' : '0%';
    $writer->addRow(Row::fromValues([
        (int) $row['id'],
        export_safe_cell($row['title']),
        export_safe_cell($row['description']),
        date('Y-m-d H:i', strtotime($row['start_datetime'])),
        $capacity,
        $booked,
        max(0, $capacity - $booked),
        $attendance,
    ]));
}

$writer->close();
