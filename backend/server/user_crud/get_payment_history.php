<?php
if (ob_get_level() === 0) ob_start();

date_default_timezone_set('Asia/Dhaka');

$isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
$cookieParams = [
    'lifetime' => 86400,
    'path' => '/',
    'domain' => '',
    'secure' => $isSecure,
    'httponly' => true,
    'samesite' => $isSecure ? 'None' : 'Lax'
];
session_set_cookie_params($cookieParams);

session_start();

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, Cache-Control");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once '../config.php';

$response = [
    'success' => false,
    'payments' => [],
    'message' => 'Not logged in'
];

if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    http_response_code(401);
    echo json_encode($response);
    exit;
}

$userId = $_SESSION['user']['id'];

try {
    $stmt = $pdo->prepare("
        SELECT 
            bk.id AS id,
            bk.id AS booking_id,
            bk.package_id,
            bk.booking_date,
            bk.start_date,
            bk.end_date,
            bk.total_price AS amount,
            bk.deposit_amount,
            bk.paid_amount,
            bk.due_amount,
            bk.payment_method AS method,
            bk.transaction_id,
            bk.payment_status AS status,
            bk.memo_number,
            bk.payment_proof,
            bk.approval_date AS payment_date,
            bk.payment_approved,
            pk.name AS package_name,
            pk.image_url AS package_image
        FROM bookings bk
        JOIN packages pk ON bk.package_id = pk.id
        WHERE bk.user_id = ?
        ORDER BY bk.booking_date DESC
    ");

    $stmt->execute([$userId]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Ensure all required fields have values
    foreach ($payments as &$payment) {
        $payment['status'] = $payment['status'] ?? 'pending';
        $payment['method'] = $payment['method'] ?? 'unknown';
        $payment['payment_date'] = $payment['payment_date'] ?? $payment['booking_date'];
        $payment['amount'] = $payment['amount'] ?? 0;
    }

    echo json_encode([
        'success' => true, 
        'payments' => $payments,
        'message' => count($payments) . ' payments found'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage(),
        'payments' => []
    ]);
}

ob_end_flush();
?>