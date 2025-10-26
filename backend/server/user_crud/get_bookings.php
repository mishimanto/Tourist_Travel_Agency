<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");
require_once '../config.php';
require_once '../check_auth.php';

$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;

if (!$user_id) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "User ID is required"]);
    exit;
}

// Verify the requesting user can only access their own data
if ($user_id != $auth_user_id) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Unauthorized access"]);
    exit;
}

$database = new Database();
$conn = $database->getConnection();

$query = "SELECT b.*, p.name as package_name 
          FROM bookings b
          LEFT JOIN packages p ON b.package_id = p.id
          WHERE b.user_id = ?
          ORDER BY b.booking_date DESC";
$stmt = $conn->prepare($query);
$stmt->bindParam(1, $user_id);
$stmt->execute();

$bookings = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $bookings[] = $row;
}

echo json_encode(["success" => true, "data" => $bookings]);
?>