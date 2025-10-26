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

require_once '../config.php';

session_start();

// Check if user is authenticated as admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Fetch about data
    try {
        $query = "SELECT * FROM about WHERE id = 1";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode([
                'success' => true,
                'data' => $row
            ]);
        } else {
            // Return empty data if no record exists
            echo json_encode([
                'success' => true,
                'data' => [
                    'company_name' => '',
                    'address' => '',
                    'phone' => '',
                    'email' => '',
                    'map_url' => '',
                    'about_text' => ''
                ]
            ]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update about data
    $data = json_decode(file_get_contents("php://input"), true);
    
    try {
        // Check if record exists
        $checkQuery = "SELECT id FROM about WHERE id = 1";
        $checkStmt = $pdo->prepare($checkQuery);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() > 0) {
            // Update existing record
            $query = "UPDATE about SET 
                      company_name = :company_name, 
                      address = :address, 
                      phone = :phone, 
                      email = :email, 
                      map_url = :map_url, 
                      about_text = :about_text, 
                      updated_at = NOW() 
                      WHERE id = 1";
        } else {
            // Insert new record
            $query = "INSERT INTO about 
                     (company_name, address, phone, email, map_url, about_text) 
                     VALUES 
                     (:company_name, :address, :phone, :email, :map_url, :about_text)";
        }
        
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':company_name', $data['company_name']);
        $stmt->bindParam(':address', $data['address']);
        $stmt->bindParam(':phone', $data['phone']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':map_url', $data['map_url']);
        $stmt->bindParam(':about_text', $data['about_text']);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'About information updated successfully'
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to update about information'
            ]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
}
?>