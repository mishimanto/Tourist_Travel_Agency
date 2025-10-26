<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Verify required files exist before including
$required_files = [
    '../config.php',
    '../mailer/PHPMailer.php',
    '../mailer/SMTP.php',
    '../fpdf/fpdf.php'
];

foreach ($required_files as $file) {
    if (!file_exists($file)) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => "Required file missing: $file"
        ]);
        exit;
    }
}

require_once '../config.php';
require_once '../mailer/PHPMailer.php';
require_once '../mailer/SMTP.php';
require_once '../fpdf/fpdf.php';

use PHPMailer\PHPMailer\PHPMailer;

function validateInput($data) {
    if (empty($data->booking_id) || !is_numeric($data->booking_id)) {
        throw new Exception("Invalid booking ID");
    }
    if (empty($data->admin_id) || !is_numeric($data->admin_id)) {
        throw new Exception("Invalid admin ID");
    }
    if (!isset($data->remarks)) {
        $data->remarks = '';
    }
}

function generateMemoNumber($bookingId) {
    return 'MEMO-' . date('Ymd') . '-' . str_pad($bookingId, 5, '0', STR_PAD_LEFT);
}

function updateBookingStatus($pdo, $bookingId, $adminId, $remarks, $memoNumber) {
    $stmt = $pdo->prepare("
        UPDATE bookings 
        SET payment_approved = 1,
            approval_date = NOW(),
            approved_by = :adminId,
            remarks = :remarks,
            memo_number = :memoNumber,
            status = 'confirmed'
        WHERE id = :bookingId
    ");
    
    return $stmt->execute([
        ':adminId' => $adminId,
        ':remarks' => $remarks,
        ':memoNumber' => $memoNumber,
        ':bookingId' => $bookingId
    ]);
}

function getBookingDetails($pdo, $bookingId) {
    $stmt = $pdo->prepare("
        SELECT b.*, u.email as customer_email, u.username as customer_name, 
               p.name as package_name, a.username as approved_by_name
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN packages p ON b.package_id = p.id
        LEFT JOIN admin a ON b.approved_by = a.id
        WHERE b.id = :bookingId
    ");
    $stmt->execute([':bookingId' => $bookingId]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        throw new Exception("Booking not found");
    }
    
    return $booking;
}

function generateMemoPDF($booking, $memoNumber) {
    // Create memo directory if it doesn't exist
    $memoDir = '../memos/';
    if (!file_exists($memoDir)) {
        if (!mkdir($memoDir, 0777, true)) {
            throw new Exception("Failed to create memo directory");
        }
    }

    // Generate PDF memo
    $pdf = new FPDF();
    $pdf->AddPage();
    
    // Set document properties
    $pdf->SetTitle('Payment Memo - ' . $memoNumber);
    $pdf->SetAuthor('TRAVIUQE Admin');
    
    // Header
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'TRAVIUQE PAYMENT MEMO', 0, 1, 'C');
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 10, 'Memo #: ' . $memoNumber, 0, 1, 'C');
    $pdf->Ln(10);
    
    // Booking details
    $pdf->SetFont('Arial', '', 12);
    
    $cellWidth = 60;
    $cellHeight = 8;
    
    $fields = [
        'Booking ID:' => $booking['id'],
        'Customer Name:' => $booking['customer_name'],
        'Package:' => $booking['package_name'],
        'Travel Dates:' => $booking['start_date'] . ' to ' . $booking['end_date'],
        'Total Amount:' => '৳' . number_format($booking['total_price'], 2),
        'Paid Amount:' => '৳' . number_format($booking['paid_amount'], 2),
        'Payment Method:' => $booking['payment_method'],
        'Approved By:' => $booking['approved_by_name'] ?? 'System',
        'Approval Date:' => $booking['approval_date']
    ];
    
    foreach ($fields as $label => $value) {
        $pdf->Cell($cellWidth, $cellHeight, $label, 0, 0);
        $pdf->Cell(0, $cellHeight, $value, 0, 1);
    }
    
    $pdf->Ln(15);
    
    // Footer
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->Cell(0, $cellHeight, 'This memo serves as official confirmation of your payment.', 0, 1);
    $pdf->Cell(0, $cellHeight, 'TRAVIUQE Travel Agency | contact@traviuqe.com | +880 XXXXXXXX', 0, 1);
    
    // Save PDF to server
    $memoFilename = 'MEMO_' . $memoNumber . '_' . time() . '.pdf';
    $memoPath = $memoDir . $memoFilename;
    $pdf->Output($memoPath, 'F');
    
    if (!file_exists($memoPath)) {
        throw new Exception("Failed to save PDF file");
    }
    
    return $memoPath;
}

function sendApprovalEmail($booking, $memoNumber, $memoPath) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'moynulislamshimanto11@gmail.com';
        $mail->Password   = 'fkismgljfuellmim';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        // Recipients
        $mail->setFrom('moynulislamshimanto11@gmail.com', 'TRAVIUQE');
        $mail->addAddress($booking['customer_email']);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Payment Approved - Memo #' . $memoNumber;
        
        $mail->Body = "
            <h2>Payment Approved</h2>
            <p>Dear {$booking['customer_name']},</p>
            <p>Your payment for booking #{$booking['id']} has been approved.</p>
            
            <h3>Booking Details</h3>
            <p><strong>Package:</strong> {$booking['package_name']}</p>
            <p><strong>Dates:</strong> {$booking['start_date']} to {$booking['end_date']}</p>
            <p><strong>Total Amount:</strong> ৳{$booking['total_price']}</p>
            <p><strong>Paid Amount:</strong> ৳{$booking['paid_amount']}</p>
            
            <h3>Memo Details</h3>
            <p><strong>Memo Number:</strong> {$memoNumber}</p>
            <p><strong>Approval Date:</strong> " . date('Y-m-d H:i:s') . "</p>
            
            <p>Please find attached your payment memo.</p>
            <p>Thank you for choosing our service!</p>
        ";

        // Attach the memo PDF
        $mail->addAttachment($memoPath, 'Payment_Memo_' . $memoNumber . '.pdf');

        return $mail->send();
    } catch (Exception $e) {
        throw new Exception("Mailer Error: " . $e->getMessage());
    }
}

try {
    // Get and validate input
    $json = file_get_contents('php://input');
    $input = json_decode($json);
    
    if (json_last_error() !== JSON_ERROR_NONE || $input === null) {
        throw new Exception("Invalid JSON input");
    }
    
    validateInput($input);
    
    $bookingId = (int)$input->booking_id;
    $remarks = filter_var($input->remarks, FILTER_SANITIZE_STRING);
    $adminId = (int)$input->admin_id;
    
    $pdo->beginTransaction();
    
    // Generate memo number
    $memoNumber = generateMemoNumber($bookingId);
    
    // Update booking status
    if (!updateBookingStatus($pdo, $bookingId, $adminId, $remarks, $memoNumber)) {
        throw new Exception("Failed to update booking status");
    }
    
    // Get booking details
    $booking = getBookingDetails($pdo, $bookingId);
    
    // Generate memo PDF
    $memoPath = generateMemoPDF($booking, $memoNumber);
    
    // Send email with memo attachment
    if (!sendApprovalEmail($booking, $memoNumber, $memoPath)) {
        throw new Exception("Failed to send approval email");
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'memo_number' => $memoNumber,
        'memo_path' => $memoPath,
        'message' => 'Payment approved and memo generated successfully'
    ]);
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Clean up memo file if it was created but transaction failed
    if (isset($memoPath) {
        @unlink($memoPath);
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>