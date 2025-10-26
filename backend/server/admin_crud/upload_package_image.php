<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config.php';

try {
    if (empty($_FILES['image'])) {
        throw new Exception("No image file uploaded");
    }

    // Path to your client public assets folder
    $uploadDir = '../../../client/public/assets/img/';
    
    // Create directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $file = $_FILES['image'];
    $fileExt = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = 'package-' . uniqid() . '.' . $fileExt;
    $filePath = $uploadDir . $fileName;

    // Check if image file is actual image
    $check = getimagesize($file['tmp_name']);
    if ($check === false) {
        throw new Exception("File is not an image");
    }

    // Check file size (max 5MB)
    if ($file['size'] > 5000000) {
        throw new Exception("File is too large (max 5MB)");
    }

    // Allow certain file formats
    $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array(strtolower($fileExt), $allowedExts)) {
        throw new Exception("Only JPG, JPEG, PNG, WEBP & GIF files are allowed");
    }

    // Move the file
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        throw new Exception("Error uploading file");
    }

    // Return the relative path that can be used in the frontend
    $relativePath = $fileName;

    echo json_encode([
        'success' => true,
        'fileName' => $relativePath,
        'message' => 'Image uploaded successfully'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>