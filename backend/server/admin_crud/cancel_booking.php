<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

while (ob_get_level()) ob_end_clean();

header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

session_start([
    'cookie_httponly' => true,
    'cookie_secure' => true,
    'cookie_samesite' => 'Strict'
]);

$input = file_get_contents("php://input");

if (empty($input)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "No input data received"
    ]);
    exit;
}

$data = json_decode($input, true);
if ($data === null) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Invalid JSON data: " . json_last_error_msg()
    ]);
    exit;
}

try {
    require_once '../config.php';

    $pdo->beginTransaction();

    // First get booking details including user email
    $bookingStmt = $pdo->prepare("
        SELECT b.*, u.email, u.username as user_name 
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        WHERE b.id = :booking_id
    ");
    $bookingStmt->execute([':booking_id' => $data['booking_id']]);
    $booking = $bookingStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        throw new Exception("Booking not found");
    }

    // Update the booking status
    $updateStmt = $pdo->prepare("
        UPDATE bookings 
        SET status = 'cancelled', 
            cancelled_by_admin = 1,
            cancellation_reason = :reason,
            cancellation_date = NOW()
        WHERE id = :booking_id
    ");
    
    $updateStmt->execute([
        ':booking_id' => $data['booking_id'],
        ':reason' => $data['reason']
    ]);

    // Load Composer's autoloader for PHPMailer
    require_once '../mailer/PHPMailer.php';
    require_once '../mailer/SMTP.php';

    // Create PHPMailer instance
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'moynulislamshimanto11@gmail.com';
        $mail->Password   = 'fkismgljfuellmim';
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        // Recipients
        $mail->setFrom('moynulislamshimanto11@gmail.com', 'TRAVIUQE');
        $mail->addAddress($booking['email'], $booking['user_name']);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Booking Cancellation - Booking #' . $data['booking_id'];
        
        $mail->Body = "
            <h1>Booking Cancellation Notice</h1>
            <p>Dear {$booking['user_name']},</p>
            <p>We are very sorry to inform you that your booking has been cancelled by our administration.</p>
            
            <h3>Booking Details</h3>
            <p><strong>Booking ID:</strong> {$data['booking_id']}</p>
            <p><strong>Cancellation Reason:</strong> {$data['reason']}</p>
            <p><strong>Cancellation Date:</strong> " . date('Y-m-d h:i:s') . "</p>
            
            <h3>Refund Information</h3>
            <p>If you have made any payments, our team will process your refund within 2-5 business days.  Please stay tuned with us.</p>
            
            <p>If you believe this cancellation was made in error, please contact our support team immediately.</p>
            
            <p>We apologize for any inconvenience this may have caused.</p>
            
            <p>Sincerely,<br>TRAVIUQE Team</p>
        ";
        
        $mail->AltBody = "Booking Cancellation Notice\n\n" .
            "Dear {$booking['user_name']},\n\n" .
            "We regret to inform you that your booking has been cancelled by our administration team.\n\n" .
            "Booking ID: {$data['booking_id']}\n" .
            "Cancellation Reason: {$data['reason']}\n" .
            "Cancellation Date: " . date('Y-m-d H:i:s') . "\n\n" .
            "Refund Information:\n" .
            "If you have made any payments, our team will process your refund within 2-5 business days. Please stay tuned with us.\n\n" .
            "If you believe this cancellation was made in error, please contact our support team immediately.\n\n" .
            "We apologize for any inconvenience this may have caused.\n\n" .
            "Sincerely,\nTRAVIUQE Team";

        $mail->send();
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        // Don't fail the whole operation if email fails
    }

    $pdo->commit();

    echo json_encode([
        "success" => true,
        "message" => "Booking cancelled successfully",
        "booking_id" => $data['booking_id'],
        "cancellation_date" => date('Y-m-d H:i:s')
    ]);

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database error",
        "error" => $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Booking cancellation failed",
        "error" => $e->getMessage()
    ]);
}
?>