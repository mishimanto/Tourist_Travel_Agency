<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$input = file_get_contents("php://input");

if (empty($input)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "No input data received"
    ]);
    exit;
}

$data = json_decode($input);
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
    require_once '../mailer/PHPMailer.php';
    require_once '../mailer/SMTP.php';

    // First get booking details
    $stmt = $pdo->prepare("
        SELECT b.*, p.name as package_name, u.username, u.email 
        FROM bookings b
        JOIN packages p ON b.package_id = p.id
        JOIN users u ON b.user_id = u.id
        WHERE b.id = :booking_id
        ORDER BY b.id DESC
    ");
    $stmt->execute([':booking_id' => $data->booking_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        throw new Exception("Booking not found");
    }

    // Safe numeric conversions
    $totalPrice = (float)$booking['total_price'];
    $paidAmount = (float)$booking['paid_amount'];
    $dueAmount  = (float)$booking['due_amount'];

    // Prepare PHPMailer
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    // Server settings
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'moynulislamshimanto11@gmail.com'; 
    $mail->Password   = 'fkismgljfuellmim'; 
    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;
    $mail->CharSet    = 'UTF-8';

    // Recipients
    $mail->setFrom('moynulislamshimanto11@gmail.com', 'TRAVIUQE');
    $mail->addAddress($booking['email'], $booking['username']);
    
    // Content
    $mail->isHTML(true);
    $mail->Subject = $data->subject ?? 'Payment Reminder - Booking #' . $booking['id'];
    
    // Email template with professional design
    $emailTemplate = '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Payment Reminder</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; background: #f9f9f9; }
            .header { background: #dc3545; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
            .content { background: white; padding: 20px; border-radius: 0 0 5px 5px; }
            .footer { margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #777; text-align: center; }
            table { width: 100%; border-collapse: collapse; margin: 15px 0; }
            table td { padding: 10px; border-bottom: 1px solid #eee; }
            table tr:last-child td { border-bottom: none; }
            .urgent { color: #dc3545; font-weight: bold; }
            .button { 
                display: inline-block; 
                padding: 10px 20px; 
                background: #007bff; 
                color: white; 
                text-decoration: none; 
                border-radius: 5px; 
                margin: 10px 0;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>Payment Reminder</h1>
                <p>Friendly reminder about your upcoming payment</p>
            </div>
            
            <div class="content">
                <p>Dear <strong>' . htmlspecialchars($booking['username']) . '</strong>,</p>
                
                <p>This is a reminder about your payment for booking <strong>#' . $booking['id'] . '</strong> (' . htmlspecialchars($booking['package_name']) . ').</p>
                
                <table>
                    <tr>
                        <td><strong>Total Amount:</strong></td>
                        <td>৳' . number_format($totalPrice, 2) . '</td>
                    </tr>
                    <tr>
                        <td><strong>Amount Paid:</strong></td>
                        <td>৳' . number_format($paidAmount, 2) . '</td>
                    </tr>
                    <tr>
                        <td><strong>Due Amount:</strong></td>
                        <td class="urgent">৳' . number_format($dueAmount, 2) . '</td>
                    </tr>';

    if (!empty($booking['due_deadline'])) {
        $isOverdue = strtotime($booking['due_deadline']) < time();
        $emailTemplate .= '
                    <tr>
                        <td><strong>Due Date:</strong></td>
                        <td class="' . ($isOverdue ? 'urgent' : '') . '">' 
                            . date('F j, Y, g:i A', strtotime($booking['due_deadline'])) . 
                            ($isOverdue ? ' (PAST DUE)' : '') . '
                        </td>
                    </tr>';
    }

    $emailTemplate .= '
                </table>
                
                <p>' . nl2br(htmlspecialchars($data->message ?? 'Please make the payment at your earliest convenience to avoid any inconvenience.')) . '</p>
                
                <a href="http://yourdomain.com/payment?booking=' . $booking['id'] . '" class="button">Make Payment Now</a>
                
                <p>If you have already made the payment, please ignore this reminder.</p>
            </div>
            
            <div class="footer">
                <p>© ' . date('Y') . ' TRAVIUQE Travel Agency. All rights reserved.</p>
                <p>This is an automated message. Please do not reply directly to this email.</p>
            </div>
        </div>
    </body>
    </html>';

    $mail->Body = $emailTemplate;
    
    // Plain text version
    $mail->AltBody = "Payment Reminder\n\n" .
        "Booking ID: {$booking['id']}\n" .
        "Package: {$booking['package_name']}\n" .
        "Total Amount: ৳{$totalPrice}\n" .
        "Amount Paid: ৳{$paidAmount}\n" .
        "Due Amount: ৳{$dueAmount}\n" .
        (!empty($booking['due_deadline']) ? "Due Date: " . date('F j, Y, g:i A', strtotime($booking['due_deadline'])) . "\n" : "") . "\n" .
        ($data->message ?? "Please make the payment at your earliest convenience to avoid any inconvenience.") . "\n\n" .
        "Payment Link: http://yourdomain.com/payment?booking={$booking['id']}\n\n" .
        "If you have already made the payment, please ignore this reminder.";

    $mail->send();
    
    // Update booking remarks
    $updateStmt = $pdo->prepare("
        UPDATE bookings 
        SET remarks = CONCAT(IFNULL(remarks, ''), DATE(NOW())) 
        WHERE id = :booking_id
    ");
    $updateStmt->execute([':booking_id' => $data->booking_id]);

    echo json_encode([
        "success" => true,
        "message" => "Payment reminder sent successfully",
        "booking_id" => $booking['id'],
        "email" => $booking['email'],
        "due_amount" => $dueAmount,
        "due_deadline" => $booking['due_deadline']
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
