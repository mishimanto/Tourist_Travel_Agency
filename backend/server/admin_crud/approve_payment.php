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

// Verify required files
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
            status = 'confirmed',
            payment_status = 'fully_paid'
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
    $memoDir = '../memos/';
    if (!file_exists($memoDir)) {
        if (!mkdir($memoDir, 0777, true)) {
            throw new Exception("Failed to create memo directory");
        }
    }

    $pdf = new FPDF();
    $pdf->AddPage();
    
    // Header with logo and company info
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->SetTextColor(31, 73, 125); // Dark blue color
    $pdf->Cell(0, 10, 'TRAVIUQE', 0, 1, 'C');
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(128, 128, 128); // Gray color
    $pdf->Cell(0, 6, 'Casara, Narayanganj, Dhaka, Bangladesh', 0, 1, 'C');
    $pdf->Cell(0, 6, 'Phone: +880 19XXXXXXXX | Email: shimzo@gmail.com', 0, 1, 'C');
    
    // Memo title and number
    $pdf->Ln(10);
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->SetTextColor(31, 73, 125);
    /*$pdf->Cell(0, 10, 'PAYMENT CONFIRMATION MEMO', 0, 1, 'C', false);*/
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, '' . $memoNumber, 0, 1, 'C');
    
    // Horizontal line
    $pdf->SetDrawColor(31, 73, 125);
    $pdf->SetLineWidth(0.5);
    $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
    $pdf->Ln(10);
    
    // Booking details section
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetTextColor(31, 73, 125);
    $pdf->Cell(0, 8, 'BOOKING DETAILS', 0, 1);
    $pdf->SetFont('Arial', '', 11);
    $pdf->SetTextColor(0, 0, 0);
    
    $cellWidth = 60;
    $cellHeight = 8;
    
    $fields = [
        'Booking ID:' => $booking['id'],
        'Customer Name:' => $booking['customer_name'],
        'Package:' => $booking['package_name'],
        'Travel Dates:' => date('d M Y', strtotime($booking['start_date'])) . ' to ' . date('d M Y', strtotime($booking['end_date'])),
    ];
    
    foreach ($fields as $label => $value) {
        $pdf->Cell($cellWidth, $cellHeight, $label, 0, 0);
        $pdf->Cell(0, $cellHeight, $value, 0, 1);
    }
    
    $pdf->Ln(5);
    
    // Payment details section
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetTextColor(31, 73, 125);
    $pdf->Cell(0, 8, 'PAYMENT INFORMATION', 0, 1);
    $pdf->SetFont('Arial', '', 11);
    $pdf->SetTextColor(0, 0, 0);
    
    $paymentFields = [
        'Total Amount:' => 'BDT ' . number_format($booking['total_price'], 2),
        'Paid Amount:' => 'BDT ' . number_format($booking['paid_amount'], 2),
        'Payment Method:' => ucfirst($booking['payment_method']),
        'Transaction Date:' => date('d M Y, h:i A', strtotime($booking['approval_date'] ?? 'now')),
        /*'Approved By:' => $booking['approved_by_name'] ?? 'System Admin',*/
    ];
    
    foreach ($paymentFields as $label => $value) {
        $pdf->Cell($cellWidth, $cellHeight, $label, 0, 0);
        $pdf->Cell(0, $cellHeight, $value, 0, 1);
    }
    
    // Footer section
    /*$pdf->SetY(-40);*/
    $pdf->Ln(10);
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->Cell(0, 8, 'This is an official confirmation of your payment.', 0, 1);
    $pdf->Cell(0, 8, 'Your tickets and other documents will be availabe in few days. We wil contact you soon.', 0, 1);
    $pdf->Cell(0, 8, 'Please present this memo when checking in for your tour package.', 0, 1);
    
    /*$pdf->SetY(-20);*/
    $pdf->Ln(30);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetTextColor(31, 73, 125);
    $pdf->Cell(0, 8, 'Thank you for choosing TRAVIUQE!', 0, 1, 'C');
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->Cell(0, 6, 'Generated on: ' . date('d M Y, h:i A'), 0, 1, 'C');
    
    $memoFilename = 'MEMO_' . $memoNumber . '.pdf';
    $memoPath = $memoDir . $memoFilename;
    $pdf->Output($memoPath, 'F');
    
    if (!file_exists($memoPath)) {
        throw new Exception("Failed to save PDF file");
    }
    
    return [
        'path' => $memoPath,
        'filename' => $memoFilename
    ];
}

function sendApprovalEmail($booking, $memoNumber, $memoPath) {
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'moynulislamshimanto11@gmail.com';
        $mail->Password   = 'fkismgljfuellmim';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        $mail->setFrom('moynulislamshimanto11@gmail.com', 'TRAVIUQE Travel Agency');
        $mail->addAddress($booking['customer_email']);
        $mail->addReplyTo('support@traviuqe.com', 'TRAVIUQE Support');

        $mail->isHTML(true);
        $mail->Subject = 'Payment Approved - Booking #' . $booking['id'] . ' - TRAVIUQE';
        
        $emailTemplate = '
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #1f497d; padding: 20px; text-align: center; }
                .header h1 { color: white; margin: 0; }
                .content { padding: 20px; background-color: #f9f9f9; }
                .footer { padding: 10px; text-align: center; font-size: 12px; color: #777; }
                .details-table { width: 100%; border-collapse: collapse; margin: 15px 0; }
                .details-table th { background-color: #1f497d; color: white; padding: 8px; text-align: left; }
                .details-table td { padding: 8px; border-bottom: 1px solid #ddd; }
                .button { 
                    display: inline-block; 
                    padding: 10px 20px; 
                    background-color: #1f497d; 
                    color: white; 
                    text-decoration: none; 
                    border-radius: 4px; 
                    margin: 10px 0;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>TRAVIUQE</h1>
                </div>
                
                <div class="content">
                    <h2>Payment Approved</h2>
                    <p>Dear ' . htmlspecialchars($booking['customer_name']) . ',</p>
                    <p>We are pleased to inform you that your payment for Booking #' . $booking['id'] . ' has been successfully approved.</p>
                    
                    <h3>Booking Summary</h3>
                    <table class="details-table">
                        <tr>
                            <th>Package</th>
                            <td>' . htmlspecialchars($booking['package_name']) . '</td>
                        </tr>
                        <tr>
                            <th>Travel Dates</th>
                            <td>' . date('d M Y', strtotime($booking['start_date'])) . ' to ' . date('d M Y', strtotime($booking['end_date'])) . '</td>
                        </tr>
                        <tr>
                            <th>Total Amount</th>
                            <td>BDT ' . number_format($booking['total_price'], 2) . '</td>
                        </tr>
                        <tr>
                            <th>Paid Amount</th>
                            <td>BDT ' . number_format($booking['paid_amount'], 2) . '</td>
                        </tr>
                        <tr>
                            <th>Payment Method</th>
                            <td>' . ucfirst($booking['payment_method']) . '</td>
                        </tr>
                        <tr>
                            <th>Payment Status</th>
                            <td>' . ucfirst($booking['payment_method']) . '</td>
                        </tr>
                        <tr>
                            <th>Memo Number</th>
                            <td>Paid</td>
                        </tr>
                        <tr>
                            <th>Approval Date</th>
                            <td>' . date('d M Y, h:i A') . '</td>
                        </tr>
                    </table>
                    
                    <p>Your payment memo is attached to this email for your records. Please keep this document safe as you may need to present it during check-in.</p>
                    
                    <p>If you have any questions about your booking, please don\'t hesitate to contact our support team.</p>
                    
                    <p>We look forward to serving you on your upcoming trip!</p>
                </div>
                
                <div class="footer">
                    <p>TRAVIUQE<br>
                    Chasara, Narayanganj, Dhaka, Bangladesh<br>
                    Phone: +880 19XXXXXXXX | Email: shimzo@gmail.com</p>
                    <p>Thank you for staying with us.</p>
                </div>
            </div>
        </body>
        </html>';

        $mail->Body = $emailTemplate;
        $mail->AltBody = "Payment Approved\n\nDear " . $booking['customer_name'] . ",\n\nYour payment for Booking #" . $booking['id'] . " has been approved.\n\nPackage: " . $booking['package_name'] . "\nTravel Dates: " . $booking['start_date'] . " to " . $booking['end_date'] . "\nTotal Amount: BDT " . number_format($booking['total_price'], 2) . "\nPaid Amount: BDT " . number_format($booking['paid_amount'], 2) . "\nMemo Number: " . $memoNumber . "\n\nPlease find attached your payment memo.\n\nThank you for choosing TRAVIUQE Travel Agency.";

        $mail->addAttachment($memoPath['path'], $memoPath['filename']);
        return $mail->send();
    } catch (Exception $e) {
        throw new Exception("Mailer Error: " . $e->getMessage());
    }
}

try {
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
    
    // Get updated booking details
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
        'memo_path' => $memoPath['path'],
        'message' => 'Payment approved and memo generated successfully'
    ]);
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    if (isset($memoPath) && file_exists($memoPath['path'])) {
        @unlink($memoPath['path']);
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}