<?php
header('Content-Type: application/json');
include 'db.php';
require_once 'csrf.php';

// Check if user is logged in and has the correct role
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'trainer'], true)) {
    die(json_encode(["success"=>false,"message"=>"Unauthorized access"]));
}

$data = json_decode(file_get_contents("php://input"), true);
if (!is_array($data) || !check_csrf($data['csrf_token'] ?? '')) {
    http_response_code(403);
    die(json_encode(["success" => false, "message" => "Invalid security token"]));
}

$roleStmt = $conn->prepare('SELECT role FROM users WHERE id = ?');
$roleStmt->bind_param('i', $_SESSION['user_id']);
$roleStmt->execute();
$currentRole = $roleStmt->get_result()->fetch_assoc()['role'] ?? '';
if (!in_array($currentRole, ['admin', 'trainer'], true)) {
    http_response_code(403);
    die(json_encode(["success" => false, "message" => "Unauthorized access"]));
}
$_SESSION['role'] = $currentRole;

$id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
$title = trim((string) ($data['title'] ?? ''));
$description = trim((string) ($data['description'] ?? ''));
$start = (string) ($data['start_datetime'] ?? '');
$capacity = filter_var($data['capacity'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1000]]);
$color = (string) ($data['color'] ?? 'Blue');
$validColors = ['Blue', 'Green', 'Red', 'Orange', 'Purple', 'Teal'];

if ($title === '' || mb_strlen($title) > 150 || mb_strlen($description) > 10000 || $capacity === false
    || !in_array($color, $validColors, true)
    || !DateTime::createFromFormat('Y-m-d\\TH:i', $start)) {
    die(json_encode(["success" => false, "message" => "Invalid class details."]));
}

// Holiday Validation
$check_ts = strtotime($start);
$year = (int)date('Y', $check_ts);
$md = date('m-d', $check_ts);
$full_date = date('Y-m-d', $check_ts);

// Fixed date Latvian holidays
$fixed_holidays = [
    '01-01', // New Year
    '05-01', // Labor Day
    '05-04', // Restoration of Independence
    '06-23', // Līgo
    '06-24', // Jāņi
    '11-18', // Proclamation Day
    '12-24', // Christmas Eve
    '12-25', // Christmas
    '12-26', // 2nd Christmas
    '12-31', // New Year's Eve
];

$is_holiday = in_array($md, $fixed_holidays);

if (!$is_holiday) {
    // Variable date holidays (Easter)
    $easter_ts = easter_date($year);
    $good_friday = date('Y-m-d', strtotime('-2 days', $easter_ts));
    $easter_sunday = date('Y-m-d', $easter_ts);
    $easter_monday = date('Y-m-d', strtotime('+1 day', $easter_ts));
    if (in_array($full_date, [$good_friday, $easter_sunday, $easter_monday])) {
        $is_holiday = true;
    }
}

if ($is_holiday) {
    die(json_encode(["success" => false, "message" => $t['holiday_error']]));
}

try {
    if ($id) {
        if ($currentRole === 'trainer') {
            $owner = $conn->prepare('SELECT id FROM classes WHERE id = ? AND created_by = ?');
            $owner->bind_param('ii', $id, $_SESSION['user_id']);
            $owner->execute();
            if ($owner->get_result()->num_rows === 0) {
                http_response_code(403);
                exit(json_encode(["success" => false, "message" => "You can only edit your own classes."]));
            }
        }
        $conn->begin_transaction();
        $lock = $conn->prepare('SELECT id FROM classes WHERE id = ? FOR UPDATE');
        $lock->bind_param('i', $id); $lock->execute();
        if ($lock->get_result()->num_rows === 0) { throw new RuntimeException('Class not found.'); }
        $count = $conn->prepare("SELECT COUNT(*) AS total FROM reservations WHERE class_id = ? AND status = 'reserved'");
        $count->bind_param('i', $id); $count->execute();
        $booked = (int) $count->get_result()->fetch_assoc()['total'];
        if ($capacity < $booked) { throw new RuntimeException("Capacity cannot be lower than the $booked existing reservation(s)."); }
        $stmt = $conn->prepare('UPDATE classes SET title=?, description=?, start_datetime=?, capacity=?, color=? WHERE id=?');
        $stmt->bind_param('sssisi', $title, $description, $start, $capacity, $color, $id);
    } else {
        $stmt = $conn->prepare('INSERT INTO classes(title,description,start_datetime,capacity,color,created_by) VALUES(?,?,?,?,?,?)');
        $stmt->bind_param('sssisi', $title, $description, $start, $capacity, $color, $_SESSION['user_id']);
    }
    $stmt->execute();
    if ($id) { $conn->commit(); }
    echo json_encode(["success" => true]);
} catch (Throwable $e) {
    if ($id && $conn->errno === 0) { $conn->rollback(); }
    error_log('Save class failed: ' . $e->getMessage());
    echo json_encode(["success" => false, "message" => $e instanceof RuntimeException ? $e->getMessage() : 'Unable to save the class.']);
}
