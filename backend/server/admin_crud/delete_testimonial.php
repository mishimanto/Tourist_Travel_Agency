<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Methods: DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../config.php';

try {
    // Get testimonial ID from query parameters
    $id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;
    
    if (!$id || $id <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'Invalid testimonial ID'
        ]);
        exit;
    }

    // Check if testimonial exists
    $checkStmt = $pdo->prepare("SELECT id FROM testimonials WHERE id = ?");
    $checkStmt->execute([$id]);
    
    if ($checkStmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode([
            'success' => false, 
            'message' => 'Testimonial not found'
        ]);
        exit;
    }

    // Delete testimonial
    $deleteStmt = $pdo->prepare("DELETE FROM testimonials WHERE id = ?");
    $deleteStmt->execute([$id]);
    
    if ($deleteStmt->rowCount() > 0) {
        echo json_encode([
            'success' => true, 
            'message' => 'Testimonial deleted successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to delete testimonial'
        ]);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?>