<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once 'config.php';
require_once 'mailer/PHPMailer.php';
require_once 'mailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

date_default_timezone_set('Asia/Dhaka');

try {
    
    $stmt = $pdo->prepare("
        SELECT * FROM bookings 
        WHERE payment_status = 'deposit_paid' 
        AND status = 'confirmed'
        AND DATEDIFF(start_date, CURDATE()) = 8
    ");
    $stmt->execute();
    $reminderBookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($reminderBookings as $booking) {
        sendMail(
            $booking['customer_email'],
            "Payment Reminder - Booking #{$booking['id']}",
            "
                <h2>Reminder: Complete Your Payment</h2>
                <p>Dear {$booking['customer_name']},</p>
                <p>Your trip is scheduled to start on <strong>{$booking['start_date']}</strong>.</p>
                <p>You have only paid the deposit. Please complete your remaining payment within <b>2 days</b>, 
                otherwise your booking will be cancelled.</p>
                <p><strong>Due Amount:</strong> {$booking['due_amount']}</p>
            "
        );
    }

    // Auto Cancel: Reminder এর ২ দিন পরও full payment না করলে
    $stmt2 = $pdo->prepare("
        SELECT * FROM bookings 
        WHERE payment_status = 'deposit_paid' 
        AND status = 'confirmed'
        AND DATEDIFF(start_date, CURDATE()) = 6
    ");
    $stmt2->execute();
    $cancelBookings = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    foreach ($cancelBookings as $booking) {
        // Update booking status
        $update = $pdo->prepare("UPDATE bookings 
                                SET status='cancelled', 
                                    payment_status='cancelled',
                                    cancellation_date = NOW(),
                                    cancellation_reason = 'Auto-cancel due to non-payment'
                                WHERE id = ?");
        $update->execute([$booking['id']]);

        // Send cancellation email
        sendMail(
            $booking['customer_email'],
            "Booking Cancelled - #{$booking['id']}",
            "
                <h2>Booking Cancelled</h2>
                <p>Dear {$booking['customer_name']},</p>
                <p>Unfortunately, your booking (ID: {$booking['id']}) has been <b>cancelled</b> because 
                you did not complete the remaining payment within the required time.</p>
                <p>If you still wish to travel, please make a new booking with full payment.</p>
            "
        );
    }

    echo json_encode([
        "success" => true,
        "message" => "Payment check completed",
        "reminders_sent" => count($reminderBookings),
        "cancelled" => count($cancelBookings)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Error occurred",
        "error" => $e->getMessage()
    ]);
}

// ========== Email Sender Function ==========
function sendMail(string $to, string $subject, string $body): void {
    $mail = new PHPMailer(true);

    try {
        // SMTP settings (same as process_payment.php)
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'moynulislamshimanto11@gmail.com';
        $mail->Password   = 'fkismgljfuellmim'; // তোমার App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        $mail->setFrom('moynulislamshimanto11@gmail.com', 'TRAVIUQE');
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body);

        $mail->send();
    } catch (Exception $e) {
        error_log("Email could not be sent to {$to}. Error: {$mail->ErrorInfo}");
    }
}
