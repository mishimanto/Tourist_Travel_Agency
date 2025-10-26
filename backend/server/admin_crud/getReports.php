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
    $search = $_GET['search'] ?? '';
    $status = $_GET['status'] ?? '';
    $startDate = $_GET['startDate'] ?? '';
    $endDate = $_GET['endDate'] ?? '';

    // Base query
    $sql = "
        SELECT 
            b.id, b.customer_name, b.customer_email, b.total_price,
            b.deposit_amount, b.paid_amount, b.due_amount,
            b.payment_method, b.payment_status, b.booking_date,
            p.name AS package_name
        FROM bookings b
        LEFT JOIN packages p ON b.package_id = p.id
        WHERE 1=1
    ";

    $params = [];

    // Search by name/email/package
    if (!empty($search)) {
        $sql .= " AND (b.customer_name LIKE ? OR b.customer_email LIKE ? OR p.name LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    // Filter by status
    if (!empty($status)) {
        $sql .= " AND b.payment_status = ?";
        $params[] = $status;
    }

    // Date range
    if (!empty($startDate) && !empty($endDate)) {
        $sql .= " AND b.booking_date BETWEEN ? AND ?";
        $params[] = $startDate;
        $params[] = $endDate;
    }

    $sql .= " ORDER BY b.booking_date DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $details = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Summary (fixed, for totals)
    $summarySQL = "
        SELECT 
            SUM(CASE WHEN payment_status = 'fully_paid' THEN paid_amount ELSE 0 END) AS total_fully_paid,
            SUM(CASE WHEN payment_status = 'deposit_paid' THEN deposit_amount ELSE 0 END) AS total_deposit_paid,
            SUM(CASE WHEN payment_status = 'deposit_paid' THEN due_amount ELSE 0 END) AS total_due,
            COUNT(CASE WHEN payment_status = 'fully_paid' THEN 1 END) AS count_fully_paid,
            COUNT(CASE WHEN payment_status = 'deposit_paid' THEN 1 END) AS count_deposit_paid,
            COUNT(CASE WHEN due_amount > 0 THEN 1 END) AS count_due
        FROM bookings
    ";
    $summary = $pdo->query($summarySQL)->fetch(PDO::FETCH_ASSOC);

    foreach ($summary as $key => $value) {
        if (is_numeric($value)) {
            $summary[$key] = number_format((float)$value, 2, '.', '');
        }
    }
    $summary['grand_total'] = number_format(
        (float)$summary['total_fully_paid'] + (float)$summary['total_deposit_paid'],
        2,
        '.',
        ''
    );


    echo json_encode([
        "success" => true,
        "summary" => $summary,
        "details" => $details
    ]);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}

?>