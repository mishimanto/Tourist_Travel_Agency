<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");
require_once '../config.php';

$data = json_decode(file_get_contents("php://input"));
$booking_id = isset($data->id) ? $data->id : null;

if (!$booking_id) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Booking ID is required"]);
    exit;
}

$database = new Database();
$conn = $database->getConnection();

// First verify the booking belongs to the user
$verify_query = "SELECT user_id FROM bookings WHERE id = ?";
$verify_stmt = $conn->prepare($verify_query);
$verify_stmt->bindParam(1, $booking_id);
$verify_stmt->execute();

if ($verify_stmt->rowCount() == 0) {
    http_response_code(404);
    echo json_encode(["success" => false, "message" => "Booking not found"]);
    exit;
}

$booking = $verify_stmt->fetch(PDO::FETCH_ASSOC);
if ($booking['user_id'] != $auth_user_id) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Unauthorized to cancel this booking"]);
    exit;
}

// Update booking status
$update_query = "UPDATE bookings SET status = 'cancelled' WHERE id = ?";
$update_stmt = $conn->prepare($update_query);
$update_stmt->bindParam(1, $booking_id);

if ($update_stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Booking cancelled successfully"]);
} else {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Failed to cancel booking"]);
}
?>