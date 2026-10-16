<?php
require_once __DIR__ . '/../api/db.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/export_helpers.php';

use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Writer\XLSX\Options;

// Security check: Admin only
export_require_admin($basePath);

if (!isset($_GET['class_id']) || !ctype_digit((string) $_GET['class_id'])) {
    http_response_code(422);
    exit('Class ID is required.');
}

$class_id = intval($_GET['class_id']);

// Fetch class title for filename
$stmt = $conn->prepare("SELECT title FROM classes WHERE id = ?");
$stmt->bind_param("i", $class_id);
$stmt->execute();
$class = $stmt->get_result()->fetch_assoc();
$title = $class['title'] ?? 'workout';
$filename = preg_replace('/[^a-z0-9_-]+/i', '_', $title) . "_participants_" . date('Y-m-d') . ".xlsx";

// Fetch participants
$stmt = $conn->prepare("
    SELECT u.name, u.email, u.phone 
    FROM users u
    JOIN reservations r ON u.id = r.user_id
    WHERE r.class_id = ? AND r.status = 'reserved'
");
$stmt->bind_param("i", $class_id);
$stmt->execute();
$result = $stmt->get_result();

$options = new Options();
$options->DEFAULT_ROW_STYLE->setFontSize(9);
$options->DEFAULT_ROW_HEIGHT = 14;
$options->setColumnWidth(7, 1);
$options->setColumnWidth(24, 2);
$options->setColumnWidth(32, 3);
$options->setColumnWidth(18, 4);
$writer = new Writer($options);
$writer->openToBrowser($filename);
$titleStyle = (new Style())->setFontBold()->setFontSize(14)->setFontColor(Color::WHITE)->setBackgroundColor(Color::DARK_BLUE);
$metaStyle = (new Style())->setFontSize(10);
$headerStyle = (new Style())->setFontBold()->setFontSize(9)->setFontColor(Color::WHITE)->setBackgroundColor(Color::BLUE);
$writer->addRow(Row::fromValues(['MyGoToGym Participant List', $title], $titleStyle));
$writer->addRow(Row::fromValues(['Generated', date('Y-m-d H:i')], $metaStyle));
$writer->addRow(Row::fromValues([]));
$writer->addRow(Row::fromValues([]));
$writer->addRow(Row::fromValues(['No.', 'Name', 'Email address', 'Phone number'], $headerStyle));

$number = 1;
while ($row = $result->fetch_assoc()) {
    $writer->addRow(Row::fromValues([
        $number++,
        export_safe_cell($row['name']),
        export_safe_cell($row['email']),
        export_safe_cell($row['phone'] ?: 'Not provided'),
    ]));
}
$writer->close();
