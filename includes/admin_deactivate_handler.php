<?php
// CHRONONAV_WEB_DOSS/includes/admin_deactivate_handler.php

session_start();
require_once '../middleware/auth_check.php';
require_once '../config/db_connect.php';
require_once '../includes/functions.php';

// Ensure the user has admin privileges
requireRole(['admin']); 
/** @var \mysqli $conn */

// --- Define the absolute base URL for redirection ---
$base_redirect_path = "/CHRONONAV_WEB_DOSS/pages/admin/settings.php";


if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST['confirm_deactivate'])) {
    // Redirect if not a valid submission or confirmation checkbox wasn't sent
    $_SESSION['message'] = "Please check the confirmation box and submit the form.";
    $_SESSION['message_type'] = "danger";
    header("Location: " . $base_redirect_path);
    exit();
}

// 1. Get Inputs and User ID
$user_id = $_SESSION['user']['id'];
$current_password = $_POST['current_password'] ?? ''; 
$confirm_deactivate = $_POST['confirm_deactivate'] ?? '';

// 2. Validate Input
if (empty($current_password)) {
    $_SESSION['message'] = "Error: Current password is required to confirm deactivation.";
    $_SESSION['message_type'] = "danger";
    header("Location: " . $base_redirect_path);
    exit();
}

// 3. Verify Current Password against Database
$stmt = $conn->prepare("SELECT password_hash FROM users WHERE id = ?");
if (!$stmt) {
    error_log("Deactivation password retrieval prepare failed: " . $conn->error);
    $_SESSION['message'] = "A critical system error occurred.";
    $_SESSION['message_type'] = "danger";
    header("Location: " . $base_redirect_path);
    exit();
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$stmt->close();

if (!$user_data || !password_verify($current_password, $user_data['password_hash'])) {
    $_SESSION['message'] = "Error: Incorrect password. Account deactivation cancelled.";
    $_SESSION['message_type'] = "danger";
    header("Location: " . $base_redirect_path);
    exit();
}

// 4. Deactivate Account (Set is_active = 0)
$stmt_deactivate = $conn->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
if ($stmt_deactivate) {
    $stmt_deactivate->bind_param("i", $user_id);
    
    if ($stmt_deactivate->execute()) {
        $_SESSION['message'] = "Your account has been successfully deactivated. You have been logged out.";
        $_SESSION['message_type'] = "success";
        $stmt_deactivate->close();
        $conn->close();
        
        // Destroy the session and redirect to the login page
        session_destroy();
        header("Location: ../../auth/login.php");
        exit();
    } else {
        error_log("Deactivation failed to update database: " . $stmt_deactivate->error);
        $_SESSION['message'] = "Database error: Failed to deactivate account.";
        $_SESSION['message_type'] = "danger";
    }
    $stmt_deactivate->close();
} else {
    error_log("Deactivation prepare failed: " . $conn->error);
    $_SESSION['message'] = "A critical system error occurred.";
    $_SESSION['message_type'] = "danger";
}

$conn->close();

// 5. Redirect back to settings on critical failure
header("Location: " . $base_redirect_path);
exit();
?>