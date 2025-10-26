<?php
$frontend_origin = "http://localhost:3000";

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: $frontend_origin");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Methods: POST, PUT, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
    http_response_code(200);
    exit();
}

header("Access-Control-Allow-Origin: $frontend_origin");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, PUT");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid or missing package ID"]);
    exit;
}

$id = (int) $_GET['id'];
$data = json_decode(file_get_contents("php://input"), true);

try {
    // Get first hotel and event (since table stores single entries)
    $hotel = !empty($data['hotels']) ? $data['hotels'][0] : null;
    $event = !empty($data['events']) ? $data['events'][0] : null;

    // Update package with all fields
    $stmt = $pdo->prepare("UPDATE packages SET 
                      name = :name, 
                      destination_id = :destination_id,
                      hotel_name = :hotel_name,
                      hotel_location = :hotel_location,
                      nights = :nights,
                      event_name = :event_name,
                      event_description = :event_description,
                      event_date = :event_date,
                      duration = :duration, 
                      price = :price, 
                      inclusions = :inclusions, 
                      exclusions = :exclusions,
                      is_featured = :is_featured,
                      image_url = :image_url
                      WHERE id = :id");


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
        ':image_url' => $data['image_url'] ?? 'package-default.jpg',
        ':id' => $id
    ]);

    $affected = $stmt->rowCount();

    if ($affected === 0) {
        throw new Exception("Package not found or no changes made");
    }

    // Get updated package
    $query = "SELECT p.*, d.name as destination_name 
              FROM packages p
              JOIN destinations d ON p.destination_id = d.id
              WHERE p.id = ?";
    $stmt2 = $pdo->prepare($query);
    $stmt2->execute([$id]);
    $updatedPackage = $stmt2->fetch(PDO::FETCH_ASSOC);

    // Format the response to match frontend expectations
    $updatedPackage['hotels'] = [[
        'hotel_name' => $updatedPackage['hotel_name'],
        'hotel_location' => $updatedPackage['hotel_location'],
        'nights' => $updatedPackage['nights']
    ]];

    $updatedPackage['events'] = [[
        'event_name' => $updatedPackage['event_name'],
        'event_description' => $updatedPackage['event_description'],
        'event_date' => $updatedPackage['event_date']
    ]];

    echo json_encode($updatedPackage);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "error" => $e->getMessage(),
        "trace" => $e->getTraceAsString()
    ]);
}
?>