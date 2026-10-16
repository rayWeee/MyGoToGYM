<?php
require __DIR__ . '/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("SELECT workout_date, muscles, weight, reps FROM workouts WHERE user_id=?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

$events = [];

while ($row = $result->fetch_assoc()) {
    $events[] = [
        'title' => $row['muscles'] . ' - ' . $row['weight'] . 'kg x ' . $row['reps'],
        'start' => $row['workout_date'],
        'allDay' => true
    ];
}

echo json_encode($events);
