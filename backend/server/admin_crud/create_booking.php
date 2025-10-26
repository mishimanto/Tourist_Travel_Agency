<?php
header('Content-Type: application/json');

$allowedOrigins = [
    'http://localhost:3000',
    'https://yourproductiondomain.com'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    header("Access-Control-Allow-Origin: http://localhost:3000"); // Default for development
}

header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Max-Age: 3600");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config.php';


session_start();

error_log("Session ID: " . session_id());
error_log("Session data: " . print_r($_SESSION, true));

error_log("User ID from session: " . ($_SESSION['user']['id'] ?? 'NULL'));
$response = [
    'success' => false,
    'message' => '',
    'booking_id' => null
];

try {
    $json = file_get_contents('php://input');
    if (empty($json)) {
        throw new Exception("No data received");
    }
    
    $data = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON data: " . json_last_error_msg());
    }
    
    $requiredFields = ['customer_name', 'customer_email', 'package_id', 'start_date', 'end_date', 'package_count', 'total_price'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    
    $user_id = isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null;

   
    $customer_name = trim($data['customer_name']);
    $customer_email = filter_var(trim($data['customer_email']), FILTER_SANITIZE_EMAIL);
    $package_id = (int)$data['package_id'];
    $start_date = $data['start_date'];
    $end_date = $data['end_date'];
    $package_count = (int)$data['package_count'];
    $total_price = (float)$data['total_price'];
    $special_requests = isset($data['special_requests']) ? trim($data['special_requests']) : null;

    if (strlen($customer_name) < 2) {
        throw new Exception("Name must be at least 2 characters");
    }
    if (!filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format");
    }
    if ($package_count < 1) {
        throw new Exception("Package count must be at least 1");
    }
    if ($total_price <= 0) {
        throw new Exception("Invalid total price");
    }

    // Check if package exists
    $package_check = $pdo->prepare("SELECT id, price FROM packages WHERE id = ?");
    $package_check->execute([$package_id]);
    $package = $package_check->fetch(PDO::FETCH_ASSOC);
    
    if (!$package) {
        throw new Exception("Selected package does not exist");
    }

    // Validate price calculation
    $expected_price = $package['price'] * $package_count;
    if (abs($total_price - $expected_price) > 0.01) { // Allow small floating point differences
        throw new Exception("Price calculation mismatch");
    }

    // Check date validity
    $today = date('Y-m-d');
    if ($start_date < $today) {
        throw new Exception("Start date cannot be in the past");
    }
    if ($end_date < $start_date) {
        throw new Exception("End date cannot be before start date");
    }


    $stmt = $pdo->prepare("
        INSERT INTO bookings (
            user_id, 
            customer_name, 
            customer_email, 
            package_id, 
            booking_date, 
            start_date, 
            end_date, 
            package_count, 
            total_price, 
            status, 
            special_requests
        ) VALUES (
            :user_id, 
            :customer_name, 
            :customer_email, 
            :package_id, 
            CURDATE(), 
            :start_date, 
            :end_date, 
            :package_count, 
            :total_price, 
            'pending', 
            :special_requests
        )
    ");

    // Bind parameters
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':customer_name', $customer_name, PDO::PARAM_STR);
    $stmt->bindParam(':customer_email', $customer_email, PDO::PARAM_STR);
    $stmt->bindParam(':package_id', $package_id, PDO::PARAM_INT);
    $stmt->bindParam(':start_date', $start_date, PDO::PARAM_STR);
    $stmt->bindParam(':end_date', $end_date, PDO::PARAM_STR);
    $stmt->bindParam(':package_count', $package_count, PDO::PARAM_INT);
    $stmt->bindParam(':total_price', $total_price);
    $stmt->bindParam(':special_requests', $special_requests, PDO::PARAM_STR);

    // Execute the query
    if ($stmt->execute()) {
        $booking_id = $pdo->lastInsertId();
        
        $response['success'] = true;
        $response['message'] = 'Booking created successfully';
        $response['booking_id'] = $booking_id;
        
        // For authenticated users, you might want to send a confirmation email
        if ($user_id) {
            // Add code here to send confirmation email or notification
        }
    } else {
        throw new Exception("Failed to create booking");
    }

} catch (PDOException $e) {
    $response['message'] = "Database error: " . $e->getMessage();
    error_log("Database error in create_booking.php: " . $e->getMessage());
    http_response_code(500);
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(400);
}

// Ensure no output before this
if (ob_get_length()) ob_clean();
echo json_encode($response);
exit;
?>