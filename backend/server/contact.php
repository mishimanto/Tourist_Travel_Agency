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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    
    // Get and validate input data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['name']) || empty(trim($data['name']))) {
        http_response_code(400);
        echo json_encode(['error' => 'Name is required']);
        exit;
    }
    
    if (!isset($data['email']) || empty(trim($data['email']))) {
        http_response_code(400);
        echo json_encode(['error' => 'Email is required']);
        exit;
    }
    
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid email format']);
        exit;
    }
    
    if (!isset($data['subject']) || empty(trim($data['subject']))) {
        http_response_code(400);
        echo json_encode(['error' => 'Subject is required']);
        exit;
    }
    
    if (!isset($data['message']) || empty(trim($data['message']))) {
        http_response_code(400);
        echo json_encode(['error' => 'Message is required']);
        exit;
    }
    
    // Insert into database
    $query = "INSERT INTO contact_submissions (name, email, subject, message) 
              VALUES (:name, :email, :subject, :message)";
    
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':name', $data['name']);
    $stmt->bindParam(':email', $data['email']);
    $stmt->bindParam(':subject', $data['subject']);
    $stmt->bindParam(':message', $data['message']);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Contact form submitted successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to save contact form']);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>