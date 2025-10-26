<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'config.php';
require_once 'class.php';

session_start([
    'cookie_httponly' => true,
    'cookie_secure' => true,
    'cookie_samesite' => 'Strict'
]);

// Get raw input and decode it
$input = file_get_contents("php://input");
$data = json_decode($input);

if (json_last_error() !== JSON_ERROR_NONE || !$data) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Invalid JSON data: " . json_last_error_msg()
    ]);
    exit;
}

// Validate required fields
if (empty($data->email) || !filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Valid email is required"
    ]);
    exit;
}

if (empty($data->booking_id)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Booking ID is required"
    ]);
    exit;
}

try {
    $user = new User();
    $pdo = $user->getPdo();

    // Generate verification code and transaction ID
    $verification_code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $transaction_id = 'TXN-' . bin2hex(random_bytes(4));
    
    // Update booking
    $stmt = $pdo->prepare("
        UPDATE bookings 
        SET transaction_id = :transaction_id,
            payment_method = :payment_method,
            paid_amount = :amount,
            due_amount = total_price - :amount,
            payment_status = 'deposit_paid',
            deposit_amount = :amount,
            deposit_date = NOW()
        WHERE id = :booking_id
    ");
    
    $stmt->execute([
        ':transaction_id' => $transaction_id,
        ':payment_method' => $data->payment_data->paymentMethod ?? '',
        ':amount' => $data->payment_data->amount ?? 0,
        ':booking_id' => $data->booking_id
    ]);

    // Store in session
    $_SESSION['verification'] = [
        'code' => $verification_code,
        'email' => $data->email,
        'transaction_id' => $transaction_id,
        'expiry' => time() + 600,
        'booking_id' => $data->booking_id
    ];

    // Send email
    $subject = 'Your Payment Verification Code';
    $message = sprintf(
        "<html><body>
        <h2>Payment Verification</h2>
        <p>Your verification code: <strong>%s</strong></p>
        <p>Transaction ID: %s</p>
        </body></html>",
        $verification_code,
        $transaction_id
    );

    $mailSent = $user->sendMail($data->email, $subject, $message);
    
    if (!$mailSent) {
        throw new Exception("Failed to send verification email");
    }

    // Return success response
    $response = [
        "success" => true,
        "message" => "Verification code sent",
        "transaction_id" => $transaction_id,
        "verification_code" => $verification_code // For testing only
    ];

    echo json_encode($response, JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database error occurred"
    ]);
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>