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
    // Get hotels for the package
    $hotelStmt = $pdo->prepare("
        SELECT 
            id, 
            package_id, 
            COALESCE(hotel_name, 'Unnamed Hotel') as hotel_name, 
            COALESCE(hotel_location, 'Location not specified') as hotel_location, 
            COALESCE(nights, 1) as nights
        FROM package_hotels 
        WHERE package_id = ?
        ORDER BY id
    ");
    $hotelStmt->execute([$packageId]);
    $hotels = $hotelStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $hotels
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>