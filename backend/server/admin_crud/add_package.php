<?php
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
    http_response_code(200);
    exit();
}

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

require_once '../config.php';

try {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!$data) throw new Exception("Invalid input data");

    $required = ['name', 'destination_id', 'duration', 'price'];
    foreach ($required as $field) {
        if (empty($data[$field])) throw new Exception("Missing required field: $field");
    }

    // Get first hotel and event (since table stores single entries)
    $hotel = !empty($data['hotels']) ? $data['hotels'][0] : null;
    $event = !empty($data['events']) ? $data['events'][0] : null;

    // Insert package
    $stmt = $pdo->prepare("INSERT INTO packages 
    (name, destination_id, hotel_name, hotel_location, nights, 
     event_name, event_description, event_date, duration, price, 
     inclusions, exclusions, is_featured, image_url) 
    VALUES 
    (:name, :destination_id, :hotel_name, :hotel_location, :nights,
     :event_name, :event_description, :event_date, :duration, :price,
     :inclusions, :exclusions, :is_featured, :image_url)");

    $success = $stmt->execute([
        ':name' => $data['name'],
        ':destination_id' => $data['destination_id'],
        ':hotel_name' => $hotel['hotel_name'] ?? '',
        ':hotel_location' => $hotel['hotel_location'] ?? '',
        ':nights' => $hotel['nights'] ?? 1,
        ':event_name' => $event['event_name'] ?? '',
        ':event_description' => $event['event_description'] ?? '',
        ':event_date' => $event['event_date'] ?? null,
        ':duration' => $data['duration'],
        ':price' => $data['price'],
        ':inclusions' => $data['inclusions'] ?? null,
        ':exclusions' => $data['exclusions'] ?? null,
        ':is_featured' => $data['is_featured'] ?? 0,
        ':image_url' => $data['image_url'] ?? 'package-default.jpg'
    ]);

    if (!$success) throw new Exception("Failed to insert package.");

    $packageId = $pdo->lastInsertId();

    // Fetch and return new package
    $stmt = $pdo->prepare("SELECT p.*, d.name as destination_name 
        FROM packages p
        JOIN destinations d ON p.destination_id = d.id
        WHERE p.id = :id");
    $stmt->execute([':id' => $packageId]);
    $newPackage = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $newPackage
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'received_data' => $data ?? null
    ]);
}
?>