<?php
// ---- Secure Session ----
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // 1 if HTTPS
session_start();

// ---- CORS Headers ----
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ---- Session check ----
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$admin_id = $_SESSION['user']['id'];

// ---- DB connection ----
require_once '../config.php';

// Parse request parameters
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$action = isset($_GET['action']) ? $_GET['action'] : null;

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            $stmt = $pdo->query("SELECT * FROM contact_submissions ORDER BY submitted_at DESC");
            $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($submissions);
            break;

        case 'PATCH':
            if ($id && $action === 'read') {
                $data = json_decode(file_get_contents('php://input'), true);
                $is_read = isset($data['is_read']) ? (int)$data['is_read'] : 0;

                $stmt = $pdo->prepare("UPDATE contact_submissions SET is_read = :is_read WHERE id = :id");
                $stmt->execute([':is_read' => $is_read, ':id' => $id]);

                echo json_encode(['success' => true]);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'ID and action required']);
            }
            break;

        case 'POST':
            if ($id && $action === 'respond') {
                $data = json_decode(file_get_contents('php://input'), true);
                $response = $data['response'] ?? '';

                $stmt = $pdo->prepare("UPDATE contact_submissions 
                    SET responded = 1, is_read = 1, response = :response, 
                        responded_at = NOW(), responded_by = :admin_id 
                    WHERE id = :id");
                $stmt->execute([
                    ':response' => $response,
                    ':admin_id' => $admin_id,
                    ':id' => $id
                ]);

                echo json_encode(['success' => true]);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'ID and action required']);
            }
            break;

        case 'DELETE':
            if ($id) {
                $stmt = $pdo->prepare("DELETE FROM contact_submissions WHERE id = :id");
                $stmt->execute([':id' => $id]);
                echo json_encode(['success' => true]);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'ID required']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
exit;