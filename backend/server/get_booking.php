<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=utf-8");

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!isset($_GET['id'])) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Booking ID is required"]);
        exit;
    }

    $bookingId = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);

    try {
        $stmt = $pdo->prepare("
            SELECT b.*, p.name AS package_name, d.name AS destination_name, p.image_url, p.duration
            FROM bookings b
            JOIN packages p ON b.package_id = p.id
            JOIN destinations d ON p.destination_id = d.id
            WHERE b.id = :booking_id
        ");
        $stmt->execute([':booking_id' => $bookingId]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($booking) {
            echo json_encode([
                "success" => true,
                "booking" => $booking
            ]);
        } else {
            http_response_code(404);
            echo json_encode(["success" => false, "message" => "Booking not found"]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
}
?>