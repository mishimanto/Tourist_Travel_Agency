<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:3000"); // frontend port
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
require_once 'config.php';

$input = json_decode(file_get_contents('php://input'), true);

if (empty($input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No input data received']);
    exit;
}

if (!isset($input['user_id']) || !isset($input['content']) || !isset($input['rating'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO testimonials (user_id, content, rating) VALUES (:user_id, :content, :rating)");
    $stmt->execute([
        ':user_id' => $input['user_id'],
        ':content' => $input['content'],
        ':rating'  => $input['rating']
    ]);

    // Insert হওয়া testimonial-এর ID
    $testimonialId = $pdo->lastInsertId();

    // User info সহ testimonial ফেচ করা
    $stmt = $pdo->prepare("SELECT t.*, u.username, u.email 
                           FROM testimonials t 
                           JOIN users u ON t.user_id = u.id 
                           WHERE t.id = :id");
    $stmt->execute([':id' => $testimonialId]);
    $testimonial = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'message' => 'Testimonial submitted successfully',
        'testimonial' => $testimonial
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
