<?php
header('Content-Type: application/json');
include 'db.php';

// Check if user is logged in and has the correct role
if(!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'trainer'])){
    die(json_encode(["success"=>false,"message"=>"Unauthorized access"]));
}

$data = json_decode(file_get_contents("php://input"), true);

$id = $data['id'] ?? '';
$title = $data['title'] ?? '';
$description = $data['description'] ?? '';
$start = $data['start_datetime'] ?? '';
$capacity = $data['capacity'] ?? 10;
$color = $data['color'] ?? '#7c5cff';

if($title == '' || $start == ''){
    die(json_encode(["success"=>false]));
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

if($id){

$stmt = $conn->prepare("
UPDATE classes
SET title=?, description=?, start_datetime=?, capacity=?, color=?
WHERE id=?
");

$stmt->bind_param("sssisi",$title,$description,$start,$capacity,$color,$id);

}else{

$stmt = $conn->prepare("
INSERT INTO classes(title,description,start_datetime,capacity,color,created_by)
VALUES(?,?,?,?,?,?)
");

$stmt->bind_param(
"sssisi",
$title,
$description,
$start,
$capacity,
$color,
$_SESSION['user_id']
);

}

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Database error: " . $conn->error]);
}