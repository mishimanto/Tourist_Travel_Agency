<?php
// Start output buffering
if (ob_get_level() === 0) ob_start();

// Set default timezone
date_default_timezone_set('Asia/Dhaka');

// Configure session cookie parameters BEFORE session_start()
$isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
$cookieParams = [
    'lifetime' => 86400, // 24 hours
    'path' => '/',
    'domain' => '', // Use current host
    'secure' => $isSecure, // Should be true in production
    'httponly' => true,
    'samesite' => $isSecure ? 'None' : 'Lax' // None requires Secure
];
session_set_cookie_params($cookieParams);

// Start session after configuring cookies
session_start();

// Set headers FIRST before any output
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, Cache-Control");

// Handle OPTIONS request for preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Prepare default response
$response = [
    'success' => false,
    'authenticated' => false,
    'user' => null,
    'session_id' => session_id(),
    'message' => 'Not authenticated'
];

try {
    // Check if user session exists and has required data
    if (isset($_SESSION['user']) && is_array($_SESSION['user'])) {
        // Validate session user data
        $requiredKeys = ['id', 'username', 'email', 'role']; // name改为username
        $valid = true;
        
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $_SESSION['user'])) {
                $valid = false;
                break;
            }
        }

        if ($valid) {
            $response = [
                'success' => true,
                'authenticated' => true,
                'user' => [
                    'id' => $_SESSION['user']['id'],
                    'username' => $_SESSION['user']['username'],
                    'email' => $_SESSION['user']['email'],
                    'role' => $_SESSION['user']['role']
                ],
                'session_id' => session_id(),
                'message' => 'Authenticated'
            ];
        } else {
            // Invalid session data - destroy session
            session_unset();
            session_destroy();
            $response['message'] = 'Invalid session data';
        }
    }
} catch (Exception $e) {
    $response['message'] = 'Server error: ' . $e->getMessage();
}

// Clean output and send JSON
while (ob_get_level() > 0) ob_end_clean();
http_response_code($response['success'] ? 200 : 401);
echo json_encode($response);
exit;