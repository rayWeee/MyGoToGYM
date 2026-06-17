<?php
header('Content-Type: application/json');
require 'db.php';

function calendar_error_response(string $message): void {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => $message
    ]);
    exit;
}

$sql = "
SELECT 
  c.id,
  c.title,
  c.description,
  c.start_datetime,
  c.capacity,
  c.color,
  u.name AS trainer_name,
  u.role AS creator_role,
  COUNT(r.id) AS booked
FROM classes c
LEFT JOIN users u ON c.created_by = u.id
LEFT JOIN reservations r ON c.id = r.class_id
GROUP BY c.id
";

try {
    $result = $conn->query($sql);
} catch (mysqli_sql_exception $e) {
    error_log('Calendar classes query failed: ' . $e->getMessage());
    calendar_error_response('Calendar classes could not be loaded from the database.');
}

if (!$result) {
    error_log('Calendar classes query failed: ' . $conn->error);
    calendar_error_response('Calendar classes could not be loaded from the database.');
}

// Map color names to their specific hex shades for the UI
$colorMap = [
    'Blue'   => '#7c5cff',
    'Green'  => '#28a745',
    'Red'    => '#dc3545',
    'Orange' => '#fd7e14',
    'Purple' => '#6f42c1',
    'Teal'   => '#20c997'
];

$events = [];

while ($row = $result->fetch_assoc()) {
    $dbColor = !empty($row["color"]) ? $row["color"] : "Blue";
    $hexColor = $colorMap[$dbColor] ?? $dbColor; // Use map, fallback to string if already hex
    
    $events[] = [
        "id" => $row["id"],
        "title" => $row["title"],
        "start" => $row["start_datetime"],
        "color" => $hexColor,

        // IMPORTANT: FullCalendar safe format
        "extendedProps" => [
            "description" => $row["description"],
            "capacity" => (int)$row["capacity"],
            "booked" => (int)$row["booked"],
            "original_color" => $dbColor, // This will be the name (e.g., "Blue")
            "trainer_name" => $row["trainer_name"],
            "creator_role" => $row["creator_role"]
        ]
    ];
}

echo json_encode($events);
