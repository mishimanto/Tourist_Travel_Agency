<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config.php';

try {
    $query = "SELECT * FROM destinations";
    $stmt = $pdo->query($query);
    $destinations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($destinations as &$destination) {
        $destination['price'] = number_format((float)$destination['price'], 2, '.', '');
    }

    echo json_encode([
        'success' => true,
        'data' => $destinations
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error'
    ]);
}
