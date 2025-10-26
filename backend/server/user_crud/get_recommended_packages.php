<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");
require_once '../config.php';
require_once '../check_auth.php';

$database = new Database();
$conn = $database->getConnection();

// Get 6 featured packages
$query = "SELECT p.*, d.name as destination_name 
          FROM packages p
          JOIN destinations d ON p.destination_id = d.id
          WHERE p.is_featured = 1
          ORDER BY RAND()
          LIMIT 6";
$stmt = $conn->prepare($query);
$stmt->execute();

$packages = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $packages[] = $row;
}

echo json_encode(["success" => true, "data" => $packages]);
?>