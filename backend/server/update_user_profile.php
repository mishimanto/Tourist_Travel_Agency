<?php
// Start output buffering and set headers
ob_start();
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Configure session
$isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
session_set_cookie_params([
    'lifetime' => 86400,
    'path' => '/',
    'secure' => $isSecure,
    'httponly' => true,
    'samesite' => $isSecure ? 'None' : 'Lax'
]);
session_start();

require_once 'config.php'; // Your PDO connection

// Handle OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Get input data
$input = json_decode(file_get_contents('php://input'), true);

try {
    // Validate session
    if (empty($_SESSION['user']['id'])) {
        throw new Exception("Session expired", 401);
    }

    // Validate input
    if (empty($input['username'])) {
        throw new Exception("Username is required", 400);
    }

    $user_id = $_SESSION['user']['id'];

    // Begin transaction
    $pdo->beginTransaction();

    // Update profile
    $stmt = $pdo->prepare("UPDATE users SET username = ?, phone = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([
        trim($input['username']),
        $input['phone'] ?? null,
        $user_id
    ]);

    // Handle email change
    if (!empty($input['newEmail']) && !empty($input['currentPassword'])) {
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($input['currentPassword'], $user['password'])) {
            throw new Exception("Invalid current password", 401);
        }

        $stmt = $pdo->prepare("UPDATE users SET email = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([trim($input['newEmail']), $user_id]);
    }

    // Commit transaction
    $pdo->commit();

    // Refresh session data
    $stmt = $pdo->prepare("SELECT id, username, email, phone, role FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $updatedUser = $stmt->fetch();

    $_SESSION['user'] = [
        'id' => $updatedUser['id'],
        'username' => $updatedUser['username'],
        'email' => $updatedUser['email'],
        'phone' => $updatedUser['phone'],
        'role' => $updatedUser['role']
    ];

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully',
        'user' => $_SESSION['user']
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

ob_end_flush();
?>