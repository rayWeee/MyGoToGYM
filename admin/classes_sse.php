<?php
require_once __DIR__ . '/../api/db.php';

if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit;
}
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
$deadline = time() + 25;

$last_id = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;

while (time() < $deadline) {
    // Check for new classes
    $stmt = $conn->prepare("SELECT id, title, NOW() as date FROM classes WHERE id > ? ORDER BY id ASC");
    $stmt->bind_param("i", $last_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $newClasses = [];
    while($row = $result->fetch_assoc()) {
        $newClasses[] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'date' => $row['date'] // you can replace with default date if needed
        ];
        $last_id = $row['id'];
    }

    if(count($newClasses) > 0) {
        echo "data: ".json_encode($newClasses)."\n\n";
        ob_flush();
        flush();
    }

    // Wait before checking again
    sleep(3);
}
