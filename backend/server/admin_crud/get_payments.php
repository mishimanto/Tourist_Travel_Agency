<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config.php';

try {
    $type = isset($_GET['type']) ? $_GET['type'] : 'deposit';
    $payments = [];

    if ($type === 'deposit') {
        $stmt = $pdo->prepare("
            SELECT 
                b.id,
                b.user_id,
                b.customer_name,
                b.customer_email,
                b.package_id,
                p.name as package_name,
                b.total_price,
                b.paid_amount,
                b.due_amount,
                b.payment_status,
                b.payment_method,
                b.due_deadline,
                b.status,
                b.booking_date,
                b.start_date,
                b.end_date,
                b.payment_approved,
                b.remarks,
                u.username,
                u.email as user_email
            FROM bookings b
            JOIN packages p ON b.package_id = p.id
            LEFT JOIN users u ON b.user_id = u.id
            WHERE b.status != 'cancelled'
            AND (b.payment_status = 'deposit_paid' OR b.payment_status = 'pending')
            AND b.due_amount > 0
            ORDER BY 
                CASE 
                    WHEN b.due_deadline IS NULL THEN 1
                    WHEN b.due_deadline < NOW() THEN 0
                    ELSE 2
                END,
                b.due_deadline ASC,
                b.booking_date DESC
        ");
    } else {
        $stmt = $pdo->prepare("
            SELECT 
                b.id,
                b.user_id,
                b.customer_name,
                b.customer_email,
                b.package_id,
                p.name as package_name,
                b.total_price,
                b.paid_amount,
                b.due_amount,
                b.payment_status,
                b.payment_method,
                b.due_deadline,
                b.status,
                b.booking_date,
                b.start_date,
                b.end_date,
                b.payment_approved,
                b.remarks,
                u.username,
                u.email as user_email
            FROM bookings b
            JOIN packages p ON b.package_id = p.id
            LEFT JOIN users u ON b.user_id = u.id
            WHERE b.status != 'cancelled'
            AND b.payment_status = 'fully_paid'
            AND b.payment_approved = 0
            ORDER BY b.booking_date DESC
        ");
    }

    $stmt->execute();
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Add additional calculated fields for each payment
    foreach ($payments as &$payment) {
        // Calculate days remaining (if due_deadline exists)
        if ($payment['due_deadline']) {
            $dueDate = new DateTime($payment['due_deadline']);
            $today = new DateTime();
            $payment['days_remaining'] = $today->diff($dueDate)->format('%r%a');
            $payment['is_overdue'] = ($payment['days_remaining'] < 0);
        } else {
            $payment['days_remaining'] = null;
            $payment['is_overdue'] = false;
        }
        
        // Determine which email to use (customer_email or user_email)
        $payment['email'] = !empty($payment['customer_email']) ? $payment['customer_email'] : $payment['user_email'];
        
        // Format amounts for display
        $payment['total_price_formatted'] = '৳' . number_format($payment['total_price'], 2);
        $payment['paid_amount_formatted'] = '৳' . number_format($payment['paid_amount'], 2);
        $payment['due_amount_formatted'] = '৳' . number_format($payment['due_amount'], 2);
        
        // Add payment reminder status
        $payment['reminder_sent'] = !empty($payment['remarks']) && 
                                   strpos($payment['remarks'], 'Payment reminder sent') !== false;
    }
    unset($payment); // Break the reference

    echo json_encode([
        'success' => true,
        'data' => $payments,
        'type' => $type
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error',
        'data' => [],
        'error' => $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'data' => []
    ]);
}
?>