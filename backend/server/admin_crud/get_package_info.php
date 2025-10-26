<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
require_once '../config.php';

// Check if package ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Package ID is required and must be numeric'
    ]);
    exit;
}

$packageId = (int)$_GET['id'];

try {
    // Get package details with destination info
    $packageStmt = $pdo->prepare("
        SELECT 
            p.*, 
            d.name as destination_name,
            d.description as destination_description,
            d.location as destination_location,
            d.image_url as destination_image
        FROM packages p
        LEFT JOIN destinations d ON p.destination_id = d.id
        WHERE p.id = ?
    ");
    $packageStmt->execute([$packageId]);
    $package = $packageStmt->fetch(PDO::FETCH_ASSOC);

    if (!$package) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Package not found'
        ]);
        exit;
    }

    // Format response data
    $responseData = [
        'id' => $package['id'],
        'name' => $package['name'],
        'description' => $package['description'] ?? null,
        'duration' => $package['duration'],
        'price' => number_format((float)$package['price'], 2, '.', ''),
        'inclusions' => $package['inclusions'] ?? null,
        'exclusions' => $package['exclusions'] ?? null,
        'image_url' => $package['image_url'] ?? $package['destination_image'] ?? 'package-default.jpg',
        'destination_name' => $package['destination_name'] ?? null,
        'destination_description' => $package['destination_description'] ?? null,
        'destination_location' => $package['destination_location'] ?? null,
        'is_featured' => (bool)($package['is_featured'] ?? false)
    ];

    echo json_encode([
        'success' => true,
        'data' => $responseData
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>