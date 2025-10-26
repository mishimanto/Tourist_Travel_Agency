<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Max-Age: 3600");

// Handle OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config.php';

// Get content type
$contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';

// Handle JSON or form-data
if (strpos($contentType, 'application/json') !== false) {
    $data = json_decode(file_get_contents("php://input"));
} else {
    $data = (object)$_POST;
    if (isset($_FILES['image'])) {
        $data->image = $_FILES['image'];
    }
}

$response = [
    "success" => false,
    "message" => "",
    "data" => null
];

try {
    if (!empty($data->name) && !empty($data->description) && !empty($data->location)) {
        // Process image upload if exists
        $image_url = null;
        if (!empty($data->image)) {
            $uploadDir = __DIR__ . '/../../../client/public/assets/img/';
            $fileName = uniqid() . '_' . basename($data->image['name']);
            $uploadPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($data->image['tmp_name'], $uploadPath)) {
                $image_url = 'assets/img/' . $fileName;
            }
        }

        $stmt = $pdo->prepare("INSERT INTO destinations (name, description, location, price, image_url, is_featured) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $data->name,
            $data->description,
            $data->location,
            $data->price ?? 0,
            $image_url,
            $data->is_featured ?? 0
        ]);

        $response = [
            "success" => true,
            "data" => [
                "id" => $pdo->lastInsertId(),
                "name" => $data->name,
                "description" => $data->description,
                "location" => $data->location,
                "price" => $data->price ?? 0,
                "image_url" => $image_url,
                "is_featured" => $data->is_featured ?? 0
            ],
            "message" => "Destination created successfully."
        ];
    } else {
        http_response_code(400);
        $response["message"] = "Required fields (name, description, location) are missing.";
    }
} catch (PDOException $e) {
    http_response_code(500);
    $response["message"] = "Database error: " . $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);
?>