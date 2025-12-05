<?php
// CHRONONAV_WEB_DOSS/includes/admin_edit_profile_handler.php

session_start();

// 1. Check dependencies
require_once '../middleware/auth_check.php'; // Ensure this path is correct: CHRONONAV_WEB_DOSS/middleware/auth_check.php
require_once '../config/db_connect.php';    // Ensure this path is correct: CHRONONAV_WEB_DOSS/config/db_connect.php
require_once 'functions.php';               // Ensure this path is correct: CHRONONAV_WEB_DOSS/includes/functions.php

// Ensure only admins can access this page
requireRole(['admin']); 

// Check if the form was submitted using POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $_SESSION['message'] = "Invalid request method.";
    $_SESSION['message_type'] = "danger";
    header("Location: ../pages/admin/view_profile.php");
    exit();
}

/** @var \mysqli $conn */ //

$user_id = $_SESSION['user']['id'];
$current_role = $_SESSION['user']['role'];

// Initialize variables with POST data
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$admin_id = null;
$department = null;

// Only fetch admin-specific fields if the user is an admin
if ($current_role === 'admin') {
    $admin_id = trim($_POST['admin_id'] ?? null);
    $department = trim($_POST['department'] ?? null);
}

$update_fields = [];
$param_types = "";
$param_values = [];

// Validation and preparing update query (Basic example)

// Name
if (!empty($name) && $name !== $_SESSION['user']['name']) {
    $update_fields[] = "name = ?";
    $param_types .= "s";
    $param_values[] = $name;
}

// Email
if (!empty($email) && $email !== $_SESSION['user']['email']) {
    // **NOTE: You should add proper email validation and uniqueness check here!**
    $update_fields[] = "email = ?";
    $param_types .= "s";
    $param_values[] = $email;
}

// Admin-specific fields
if ($current_role === 'admin') {
    if ($admin_id !== null && $admin_id !== $_SESSION['user']['admin_id']) {
        $update_fields[] = "admin_id = ?";
        $param_types .= "s";
        $param_values[] = $admin_id;
    }
    if ($department !== null && $department !== $_SESSION['user']['department']) {
        $update_fields[] = "department = ?";
        $param_types .= "s";
        $param_values[] = $department;
    }
}

$profile_img_path = null;

// 2. Handle Profile Image Upload
if (isset($_FILES['profile_img']) && $_FILES['profile_img']['error'] === UPLOAD_ERR_OK) {
    $target_dir = "../uploads/profiles/";
    
    // Ensure the target directory exists
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $image_file = $_FILES['profile_img'];
    $file_extension = strtolower(pathinfo($image_file['name'], PATHINFO_EXTENSION));
    $new_file_name = $user_id . '_' . time() . '.' . $file_extension;
    $target_file = $target_dir . $new_file_name;

    // Check file size and type (add more security checks here!)
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    if (in_array($file_extension, $allowed_types) && $image_file['size'] <= 5000000) { // 5MB limit
        if (move_uploaded_file($image_file["tmp_name"], $target_file)) {
            // Success: path stored relative to the project root for the DB
            $profile_img_path = "uploads/profiles/" . $new_file_name;
            $update_fields[] = "profile_img = ?";
            $param_types .= "s";
            $param_values[] = $profile_img_path;

            // Optional: Delete old image if it's not the default
            $old_img = $_SESSION['user']['profile_img'] ?? '';
            if (!empty($old_img) && $old_img !== 'uploads/profiles/default-avatar.png') {
                $old_file_path = "../" . $old_img;
                if (file_exists($old_file_path)) {
                    unlink($old_file_path);
                }
            }
        } else {
            $_SESSION['message'] = "Sorry, there was an error uploading your file.";
            $_SESSION['message_type'] = "warning";
            header("Location: ../pages/admin/view_profile.php");
            exit();
        }
    } else {
        $_SESSION['message'] = "Invalid file type or size. Only JPG, JPEG, PNG, GIF, and max 5MB allowed.";
        $_SESSION['message_type'] = "warning";
        header("Location: ../pages/admin/view_profile.php");
        exit();
    }
}

// 3. Build and Execute the SQL Update
if (empty($update_fields)) {
    $_SESSION['message'] = "No changes were submitted.";
    $_SESSION['message_type'] = "info";
    header("Location: ../pages/admin/view_profile.php");
    exit();
}

$set_clause = implode(", ", $update_fields);
$sql = "UPDATE users SET {$set_clause} WHERE id = ?";
$param_types .= "i";
$param_values[] = $user_id;

$stmt = $conn->prepare($sql);

if ($stmt) {
    // Bind parameters dynamically
    $stmt->bind_param($param_types, ...$param_values);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Profile updated successfully!";
        $_SESSION['message_type'] = "success";

        // Update session variables (Crucial step!)
        if (isset($name)) $_SESSION['user']['name'] = $name;
        if (isset($email)) $_SESSION['user']['email'] = $email;
        if (isset($admin_id)) $_SESSION['user']['admin_id'] = $admin_id;
        if (isset($department)) $_SESSION['user']['department'] = $department;
        if (isset($profile_img_path)) $_SESSION['user']['profile_img'] = $profile_img_path;

    } else {
        error_log("Profile update failed (admin): " . $stmt->error);
        $_SESSION['message'] = "Database error: Profile could not be updated.";
        $_SESSION['message_type'] = "danger";
    }
    $stmt->close();
} else {
    error_log("Database query preparation failed (admin_edit_profile_handler): " . $conn->error);
    $_SESSION['message'] = "A critical system error occurred.";
    $_SESSION['message_type'] = "danger";
}

$conn->close();

// 4. Redirect back to the profile page
header("Location: ../pages/admin/view_profile.php");
exit();
?>