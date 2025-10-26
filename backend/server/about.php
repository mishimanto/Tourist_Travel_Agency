<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'config.php';

try {
    
    $query = "SELECT * FROM about WHERE id = 1";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    
    $companyInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($companyInfo) {
        echo json_encode([
            'success' => true,
            'data' => $companyInfo
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Company information not found'
        ]);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>