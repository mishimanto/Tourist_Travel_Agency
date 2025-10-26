<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

require_once '../config.php';

try {
    // Verify database connection
    if (!$pdo) {
        throw new Exception("Database connection failed");
    }

    $query = "SELECT 
                b.*, 
                p.name as package_name,
                d.name as destination_name
              FROM bookings b
              LEFT JOIN packages p ON b.package_id = p.id
              LEFT JOIN destinations d ON p.destination_id = d.id
              WHERE b.status IN ('confirmed', 'cancelled')
              ORDER BY b.id DESC";

    $stmt = $pdo->query($query);
    
    if (!$stmt) {
        throw new Exception("Failed to execute query");
    }

    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Ensure we always return an array, even if empty
    if (!is_array($bookings)) {
        $bookings = [];
    }

    // Format the response consistently
    $response = [
        'success' => true,
        'data' => $bookings
    ];

    echo json_encode($response);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'data' => []
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'data' => []
    ]);
}
?>