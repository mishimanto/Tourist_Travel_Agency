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
    'bookings' => [],
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
            b.id,
            b.package_id,
            b.start_date,
            b.end_date,
            b.customer_name,
            b.customer_email,
            b.status,
            p.name AS package_name,
            p.image_url,
            b.total_price,
            b.deposit_amount,
            b.due_amount,
            b.booking_date,
            b.paid_amount,
            b.payment_status,
            b.memo_number,
            b.payment_approved,
            b.approval_date,
            b.payment_proof
        FROM bookings b
        JOIN packages p ON b.package_id = p.id
        WHERE b.user_id = :user_id
        ORDER BY b.booking_date DESC
    ");
    $stmt->execute(['user_id' => $userId]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Add memo path to each booking if available
    foreach ($bookings as &$booking) {
        if ($booking['memo_number']) {
            $memoFilename = 'MEMO_' . $booking['memo_number'] . '.pdf';
            $memoPath = '../memos/' . $memoFilename;
            if (file_exists($memoPath)) {
                $booking['memo_url'] = 'http://localhost/Tourist_Travel_Agency/backend/server/memos/' . $memoFilename;
            }
        }
    }

    $response['success'] = true;
    $response['bookings'] = $bookings;
    $response['message'] = 'Bookings retrieved successfully';

    http_response_code(200);
} catch (PDOException $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
    http_response_code(500);
}

while (ob_get_level() > 0) ob_end_clean();
echo json_encode($response);
exit;

?>