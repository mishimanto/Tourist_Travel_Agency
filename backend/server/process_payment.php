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
    require_once 'config.php';

    $pdo->beginTransaction();

    // First get booking details to check payment due date
    $checkStmt = $pdo->prepare("SELECT paid_amount, total_price, due_deadline FROM bookings WHERE id = :booking_id");
    $checkStmt->execute([':booking_id' => $data->booking_id]);
    $existingBooking = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$existingBooking) {
        throw new Exception("Booking not found");
    }

    // Check if payment due date has passed
    if ($existingBooking['due_deadline'] && strtotime($existingBooking['due_deadline']) < time()) {
        throw new Exception("Payment cannot be processed as the due date has passed");
    }

    // Get user email for sending confirmation
    $userStmt = $pdo->prepare("SELECT username, email FROM users WHERE id = :user_id");
    $userStmt->execute([':user_id' => $data->user_id]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception("User not found");
    }

    // Calculate new paid amount
    $newPaidAmount = (float)$existingBooking['paid_amount'] + (float)$data->payment_data->amount;
    $isFullPaymentNow = ($newPaidAmount >= (float)$existingBooking['total_price']);

    // Determine payment status
    $payment_status = $isFullPaymentNow ? 'fully_paid' : 'deposit_paid';

    // Set payment due date (7 days from now) if this is the first installment payment
    $paymentDueDate = $existingBooking['due_deadline'];
    if (!$paymentDueDate && !$isFullPaymentNow) {
        $paymentDueDate = date('Y-m-d 23:59:59', strtotime('+7 days'));
    }

    // Prepare the update statement
    $stmt = $pdo->prepare("
        UPDATE bookings 
        SET 
            user_id = :user_id,
            payment_method = :payment_method,
            paid_amount = :paid_amount,
            due_amount = total_price - :paid_amount,
            payment_status = :payment_status,
            deposit_amount = CASE 
                WHEN :payment_status = 'deposit_paid' AND deposit_amount = 0 THEN :amount 
                ELSE deposit_amount 
            END,
            deposit_date = CASE 
                WHEN :payment_status = 'deposit_paid' AND deposit_date IS NULL THEN NOW() 
                ELSE deposit_date 
            END,
            full_payment_date = CASE 
                WHEN :payment_status = 'fully_paid' THEN NOW() 
                ELSE full_payment_date 
            END,
            due_deadline = :due_deadline,
            number = :number,
            pin = :pin,
            status = 'confirmed'
        WHERE id = :booking_id
    ");

    if (empty($data->user_id)) {
        throw new Exception("User ID is required for booking");
    }

    $stmt->execute([
        ':user_id' => $data->user_id,
        ':payment_method' => $data->payment_data->paymentMethod ?? '',
        ':amount' => $data->payment_data->amount,
        ':paid_amount' => $newPaidAmount,
        ':payment_status' => $payment_status,
        ':due_deadline' => $paymentDueDate,
        ':number' => $data->payment_data->number ?? '',
        ':pin' => $data->payment_data->pin ?? '',
        ':booking_id' => $data->booking_id
    ]);

    // Send confirmation email
    require_once 'mailer/PHPMailer.php';
    require_once 'mailer/SMTP.php';

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
        $mail->addAddress($user['email']);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Payment Confirmation - Booking #' . $data->booking_id;
        
        $mail->Body = '
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Payment Confirmation</title>
</head>
<body style="margin:0; padding:0; background:#f4f6f8; font-family: Arial, sans-serif; color:#333;">
  <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
    <tr>
      <td align="center" style="padding:20px 0;">
        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" style="background:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 4px 10px rgba(0,0,0,0.1);">
          
          <!-- Header -->
          <tr>
            <td style="background: linear-gradient(90deg, #007bff, #00c6ff); padding:30px; text-align:center; color:#fff;">
              <h1 style="margin:0; font-size:24px;">Payment Confirmation</h1>
              <p style="margin:5px 0 0; font-size:14px;">Thank you for booking with TRAVIUQE</p>
            </td>
          </tr>

          <!-- Body -->
          <tr>
            <td style="padding:30px;">
              <p>Dear <strong>'.$user['username'].'</strong>,</p>
              <p>We have received your payment. Here are your booking details:</p>
              
              <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; margin-top:15px;">
                <tr>
                  <td style="padding:8px; border-bottom:1px solid #eee;"><strong>Booking ID:</strong></td>
                  <td style="padding:8px; border-bottom:1px solid #eee;">'.$data->booking_id.'</td>
                </tr>
                <tr>
                  <td style="padding:8px; border-bottom:1px solid #eee;"><strong>Amount Paid:</strong></td>
                  <td style="padding:8px; border-bottom:1px solid #eee;">৳'.$data->payment_data->amount.'</td>
                </tr>
                <tr>
                  <td style="padding:8px; border-bottom:1px solid #eee;"><strong>Payment Method:</strong></td>
                  <td style="padding:8px; border-bottom:1px solid #eee;">'.ucfirst($data->payment_data->paymentMethod).'</td>
                </tr>
                <tr>
                  <td style="padding:8px; border-bottom:1px solid #eee;"><strong>Status:</strong></td>
                  <td style="padding:8px; border-bottom:1px solid #eee;">'.ucfirst($payment_status).'</td>
                </tr>
                <tr>
                  <td style="padding:8px; border-bottom:1px solid #eee;"><strong>Total Paid:</strong></td>
                  <td style="padding:8px; border-bottom:1px solid #eee;">৳'.$newPaidAmount.'</td>
                </tr>';

if ($paymentDueDate && !$isFullPaymentNow) {
  $mail->Body .= '
                <tr>
                  <td style="padding:8px; border-bottom:1px solid #eee;"><strong>Due Payment Deadline:</strong></td>
                  <td style="padding:8px; border-bottom:1px solid #eee;">'.date('F j, Y, g:i A', strtotime($paymentDueDate)).'</td>
                </tr>';
}

$mail->Body .= '
                <tr>
                  <td style="padding:8px;"><strong>Remaining Balance:</strong></td>
                  <td style="padding:8px;">৳'.((float)$existingBooking['total_price'] - $newPaidAmount).'</td>
                </tr>
              </table>

              <div style="margin-top:20px; padding:15px; background:#e9f7ef; border:1px solid #c3e6cb; border-radius:6px; color:#155724;">
                <strong>✔ Your booking is confirmed!</strong><br>
                Please keep this email for your records.
              </div>

              <p style="margin-top:20px;">If you have any questions, feel free to contact our support team.</p>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="background:#f0f0f0; text-align:center; padding:15px; font-size:12px; color:#555;">
              © '.date("Y").' TRAVIUQE Travel Agency. All rights reserved.
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
';

        
        $mail->AltBody = "Payment Confirmation\nBooking ID: {$data->booking_id}\nAmount: {$data->payment_data->amount}\nMethod: {$data->payment_data->paymentMethod}\nStatus: {$payment_status}\nTotal Paid: {$newPaidAmount}\n" . 
            ($paymentDueDate && !$isFullPaymentNow ? "Payment Due Date: " . date('F j, Y', strtotime($paymentDueDate)) . "\n" : "") . 
            "Remaining Balance: " . ((float)$existingBooking['total_price'] - $newPaidAmount);

        $mail->send();
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
    }

    $pdo->commit();

    echo json_encode([
        "success" => true,
        "message" => "Payment processed successfully",
        "booking_id" => $data->booking_id,
        "payment_status" => $payment_status,
        "due_deadline" => $paymentDueDate,
        "full_payment_date" => $isFullPaymentNow ? date('Y-m-d H:i:s') : null
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
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>