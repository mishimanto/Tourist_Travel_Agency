<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
require_once 'config.php'; // Your database configuration file

try {
    // Initialize the stats array
    $stats = [];
    
    // 1. Total Bookings
    $query = "SELECT COUNT(*) as total_bookings FROM bookings";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    $stats['totalBookings'] = (int)$row['total_bookings'];
    
    // 2. Total Revenue
    $query = "SELECT SUM(total_price) as total_revenue FROM bookings WHERE status = 'confirmed'";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    $stats['totalRevenue'] = (float)$row['total_revenue'] ?? 0;
    
    // 3. Active Users (users who have booked in the last 30 days)
    $query = "SELECT COUNT(DISTINCT user_id) as active_users 
              FROM bookings 
              WHERE booking_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    $stats['activeUsers'] = (int)$row['active_users'];
    
    // 4. Pending Bookings
    $query = "SELECT COUNT(*) as pending_bookings FROM bookings WHERE status = 'pending'";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    $stats['pendingBookings'] = (int)$row['pending_bookings'];
    
    // 5. Featured Packages
    $query = "SELECT COUNT(*) as featured_packages FROM packages WHERE is_featured = 1";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    $stats['featuredPackages'] = (int)$row['featured_packages'];
    
    // 6. Percentage changes (example calculations)
    // Total Bookings change (compared to previous month)
    $query = "SELECT COUNT(*) as last_month_bookings 
              FROM bookings 
              WHERE booking_date BETWEEN DATE_SUB(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), INTERVAL DAY(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))-1 DAY) 
              AND LAST_DAY(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    $lastMonthBookings = (int)$row['last_month_bookings'];
    $stats['bookingChangePercent'] = $lastMonthBookings > 0 ? 
        round((($stats['totalBookings'] - $lastMonthBookings) / $lastMonthBookings) * 100) : 0;
    
    // Revenue change (compared to previous month)
    $query = "SELECT SUM(total_price) as last_month_revenue 
              FROM bookings 
              WHERE status = 'confirmed' 
              AND booking_date BETWEEN DATE_SUB(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), INTERVAL DAY(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))-1 DAY) 
              AND LAST_DAY(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    $lastMonthRevenue = (float)$row['last_month_revenue'] ?? 0;
    $stats['revenueChangePercent'] = $lastMonthRevenue > 0 ? 
        round((($stats['totalRevenue'] - $lastMonthRevenue) / $lastMonthRevenue) * 100) : 0;
    
    // Active users change (compared to previous month)
    $query = "SELECT COUNT(DISTINCT user_id) as last_month_active_users 
              FROM bookings 
              WHERE booking_date BETWEEN DATE_SUB(DATE_SUB(CURDATE(), INTERVAL 2 MONTH), INTERVAL DAY(DATE_SUB(CURDATE(), INTERVAL 2 MONTH))-1 DAY) 
              AND LAST_DAY(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    $lastMonthActiveUsers = (int)$row['last_month_active_users'];
    $stats['activeUsersChangePercent'] = $lastMonthActiveUsers > 0 ? 
        round((($stats['activeUsers'] - $lastMonthActiveUsers) / $lastMonthActiveUsers) * 100) : 0;
    
    // Return the stats
    echo json_encode($stats);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to fetch statistics',
        'message' => $e->getMessage()
    ]);
}
?>