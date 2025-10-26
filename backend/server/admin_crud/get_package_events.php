<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
require_once '../config.php';

// Check if package ID is provided
if (!isset($_GET['package_id']) || !is_numeric($_GET['package_id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Package ID is required and must be numeric'
    ]);
    exit;
}

$packageId = (int)$_GET['package_id'];

try {
    // Get events for the package
    $eventStmt = $pdo->prepare("
        SELECT 
            id, 
            package_id, 
            COALESCE(event_name, 'Unnamed Event') as event_name, 
            COALESCE(event_description, 'No description available') as event_description, 
            event_date
        FROM package_events 
        WHERE package_id = ?
        ORDER BY COALESCE(event_date, '9999-12-31'), id
    ");
    $eventStmt->execute([$packageId]);
    $events = $eventStmt->fetchAll(PDO::FETCH_ASSOC);

    // Format dates
    foreach ($events as &$event) {
        if ($event['event_date']) {
            $event['formatted_date'] = date('F j, Y', strtotime($event['event_date']));
        }
    }

    echo json_encode([
        'success' => true,
        'data' => $events
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>