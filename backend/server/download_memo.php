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

header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once 'config.php';

if (!isset($_GET['memo'])) {
    http_response_code(400);
    die('Memo number not provided');
}

$memoNumber = $_GET['memo'];
$memoFilename = 'MEMO_' . $memoNumber . '.pdf';
$memoPath = '../memos/' . $memoFilename;

// Verify memo exists in database and user has access
try {
    $stmt = $pdo->prepare("
        SELECT b.id FROM bookings b
        WHERE b.memo_number = :memo_number
        AND b.user_id = :user_id
        LIMIT 1
    ");
    $stmt->execute([
        ':memo_number' => $memoNumber,
        ':user_id' => $_SESSION['user']['id']
    ]);
    
    if (!$stmt->fetch()) {
        http_response_code(404);
        die('Memo not found or access denied');
    }
} catch (PDOException $e) {
    http_response_code(500);
    die('Database error');
}

// Check if file exists
if (!file_exists($memoPath)) {
    http_response_code(404);
    die('Memo file not found');
}

// Record download in database
try {
    $stmt = $pdo->prepare("
        INSERT INTO memo_downloads 
        (user_id, memo_number, downloaded_at)
        VALUES (:user_id, :memo_number, NOW())
    ");
    $stmt->execute([
        ':user_id' => $_SESSION['user']['id'],
        ':memo_number' => $memoNumber
    ]);
} catch (PDOException $e) {
    error_log('Download tracking failed: ' . $e->getMessage());
}

// Serve the file with proper headers
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $memoFilename . '"');
header('Content-Length: ' . filesize($memoPath));
readfile($memoPath);

while (ob_get_level() > 0) ob_end_clean();
exit;
?>