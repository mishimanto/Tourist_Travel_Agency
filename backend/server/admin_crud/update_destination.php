<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config.php';

try {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!$data) {
        throw new Exception("Invalid input data");
    }

    $id = isset($_GET['id']) ? $_GET['id'] : null;
    
    if (!$id) {
        throw new Exception("Destination ID is required");
    }

    // Validate required fields
    $requiredFields = ['name', 'description', 'location'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            throw new Exception("Field {$field} is required");
        }
    }

    // Set default values for optional fields
    $price = isset($data['price']) ? (float)$data['price'] : 0;
    $image_url = $data['image_url'] ?? null;
    $is_featured = $data['is_featured'] ?? 0;

    // Prepare the update statement
    $query = "UPDATE destinations SET 
              name = :name,
              description = :description,
              location = :location,
              price = :price,
              image_url = :image_url,
              is_featured = :is_featured
              WHERE id = :id";
    
    $stmt = $pdo->prepare($query);
    
    $success = $stmt->execute([
        ':name' => $data['name'],
        ':description' => $data['description'],
        ':location' => $data['location'],
        ':price' => $price,
        ':image_url' => $image_url,
        ':is_featured' => $is_featured,
        ':id' => $id
    ]);

    if (!$success) {
        throw new Exception("Failed to update destination");
    }

    // Get the updated destination
    $query = "SELECT * FROM destinations WHERE id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$id]);
    $destination = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$destination) {
        throw new Exception("Failed to fetch updated destination");
    }

    // Format price
    $destination['price'] = number_format((float)$destination['price'], 2, '.', '');

    echo json_encode([
        'success' => true,
        'data' => $destination,
        'message' => 'Destination updated successfully'
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