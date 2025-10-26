<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config.php';

try {
    // Get the ID from query parameters
    $id = isset($_GET['id']) ? $_GET['id'] : null;
    
    if (!$id) {
        throw new Exception("Destination ID is required");
    }

    // Prepare the delete statement
    $query = "DELETE FROM destinations WHERE id = :id";
    $stmt = $pdo->prepare($query);
    
    $success = $stmt->execute([':id' => $id]);

    if (!$success) {
        throw new Exception("Failed to delete destination");
    }

    // Check if any row was actually deleted
    if ($stmt->rowCount() === 0) {
        throw new Exception("No destination found with ID: $id");
    }

    echo json_encode([
        'success' => true,
        'message' => 'Destination deleted successfully'
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>