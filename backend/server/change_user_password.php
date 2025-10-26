<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

session_start();
require_once 'config.php'; // Your database connection

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate session
    if (empty($_SESSION['user']['id'])) {
        throw new Exception("Session expired", 401);
    }

    // Validate input
    if (empty($input['currentPassword']) || empty($input['newPassword'])) {
        throw new Exception("Both current and new password are required", 400);
    }

    if (strlen($input['newPassword']) < 8) {
        throw new Exception("Password must be at least 8 characters", 400);
    }

    $user_id = $_SESSION['user']['id'];

    // Verify current password
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($input['currentPassword'], $user['password'])) {
        throw new Exception("Current password is incorrect", 401);
    }

    // Update password
    $hashedPassword = password_hash($input['newPassword'], PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->execute([$hashedPassword, $user_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Password changed successfully'
    ]);

} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>