<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config.php';

$input = json_decode(file_get_contents('php://input'));
$bookingId = filter_var($input->booking_id, FILTER_SANITIZE_NUMBER_INT);
$remarks = filter_var($input->remarks, FILTER_SANITIZE_STRING);
$adminId = filter_var($input->admin_id, FILTER_SANITIZE_NUMBER_INT);

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        UPDATE bookings 
        SET payment_approved = 0,
            status = 'cancelled',
            remarks = :remarks,
            approved_by = :adminId
        WHERE id = :bookingId
    ");

    $stmt->execute([
        ':remarks' => $remarks,
        ':adminId' => $adminId,
        ':bookingId' => $bookingId
    ]);

    // Get booking details for email
    $stmt = $pdo->prepare("
        SELECT b.*, u.email as customer_email, u.username as customer_name
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        WHERE b.id = :bookingId
    ");
    $stmt->execute([':bookingId' => $bookingId]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    // Send rejection email
    require_once '../mailer/PHPMailer.php';
    require_once '../mailer/SMTP.php';

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'moynulislamshimanto11@gmail.com';
    $mail->Password = 'fkismgljfuellmim';
    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = 465;

    $mail->setFrom('moynulislamshimanto11@gmail.com', 'TRAVIUQE');
    $mail->addAddress($booking['customer_email']);

    $mail->isHTML(true);
    $mail->Subject = 'Payment Rejected - Booking #' . $booking['id'];
    
    $mail->Body = "
        <h2>Payment Rejected</h2>
        <p>Dear {$booking['customer_name']},</p>
        <p>Your payment for booking #{$booking['id']} has been rejected.</p>
        
        <h3>Reason</h3>
        <p>{$remarks}</p>
        
        <h3>Booking Details</h3>
        <p><strong>Package:</strong> {$booking['package_name']}</p>
        <p><strong>Dates:</strong> {$booking['start_date']} to {$booking['end_date']}</p>
        <p><strong>Amount Paid:</strong> ৳{$booking['paid_amount']}</p>
        
        <p>Please contact our support team for further assistance.</p>
    ";

    $mail->send();
    $pdo->commit();

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>