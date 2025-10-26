<?php
header('Content-Type: application/json');
include('config.php');
session_start();

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(["success" => false, "message" => "Not logged in"]);
    exit;
}

$admin_id = $_SESSION['admin_id'];
$data = json_decode(file_get_contents('php://input'), true);

$username = $data['username'] ?? '';
$phone = $data['phone'] ?? '';
$currentPassword = $data['currentPassword'] ?? '';
$newEmail = $data['newEmail'] ?? '';

try {
    // First verify current password if email is being changed
    if (!empty($newEmail)) {
        $stmt = $conn->prepare("SELECT password FROM admin WHERE id = ?");
        $stmt->bind_param("i", $admin_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $admin = $result->fetch_assoc();
        
        if (!password_verify($currentPassword, $admin['password'])) {
            echo json_encode(["success" => false, "message" => "Current password is incorrect"]);
            exit;
        }
        
        // Check if new email already exists
        $stmt = $conn->prepare("SELECT id FROM admin WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $newEmail, $admin_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            echo json_encode(["success" => false, "message" => "Email already in use"]);
            exit;
        }
    }

    // Update admin profile
    if (!empty($newEmail)) {
        $stmt = $conn->prepare("UPDATE admin SET username = ?, email = ?, phone = ? WHERE id = ?");
        $stmt->bind_param("sssi", $username, $newEmail, $phone, $admin_id);
    } else {
        $stmt = $conn->prepare("UPDATE admin SET username = ?, phone = ? WHERE id = ?");
        $stmt->bind_param("ssi", $username, $phone, $admin_id);
    }

    if ($stmt->execute()) {
        // Get updated user data
        $stmt = $conn->prepare("SELECT username, email, phone FROM admin WHERE id = ?");
        $stmt->bind_param("i", $admin_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        echo json_encode([
            "success" => true,
            "message" => "Profile updated successfully",
            "user" => $user
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Update failed"]);
    }
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}
?>