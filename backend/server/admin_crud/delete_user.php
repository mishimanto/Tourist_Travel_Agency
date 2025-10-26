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
    $id = isset($_GET['id']) ? $_GET['id'] : null;
    
    if (!$id) {
        throw new Exception("User ID is required");
    }

    // First check if user exists and is not admin
    $checkQuery = "SELECT id FROM users WHERE id = ? AND role != 'admin'";
    $checkStmt = $pdo->prepare($checkQuery);
    $checkStmt->execute([$id]);
    
    if ($checkStmt->rowCount() === 0) {
        throw new Exception("User not found or cannot delete admin users");
    }

    // Delete the user
    $query = "DELETE FROM users WHERE id = ?";
    $stmt = $pdo->prepare($query);
    $success = $stmt->execute([$id]);

    if (!$success) {
        throw new Exception("Failed to delete user");
    }

    echo json_encode([
        'success' => true,
        'message' => 'User deleted successfully'
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