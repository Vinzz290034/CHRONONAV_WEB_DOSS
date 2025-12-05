<?php
// CHRONONAV_WEB_DOSS/includes/add_reminder_handler.php (RENAMED FROM admin_add_reminder_handler.php)

session_start();
require_once '../middleware/auth_check.php';
require_once '../config/db_connect.php';
require_once 'functions.php';

// Ensure only admins can access this reminder creation endpoint
requireRole(['admin']); 
/** @var \mysqli $conn */

// --- Define the absolute base URL for redirection ---
$base_redirect_path = "/CHRONONAV_WEB_DOSS/pages/admin/schedule.php";


if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    // Redirect if accessed directly (using absolute path)
    header("Location: " . $base_redirect_path);
    exit();
}

// 1. Sanitize and Validate Inputs
$user_id = $_SESSION['user']['id'];
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$due_date = trim($_POST['due_date'] ?? '');
$due_time = trim($_POST['due_time'] ?? null);

// Simple validation
if (empty($title) || empty($due_date) || empty($description)) {
    $_SESSION['message'] = "Please fill in all required fields (Title, Description, Date).";
    $_SESSION['message_type'] = "danger";
    header("Location: " . $base_redirect_path . "?date=" . $due_date);
    exit();
}

// Ensure due_time is treated as NULL if it was left empty in the form
if (empty($due_time)) {
    $due_time = null;
}


// 2. Insert into the reminders Database table
// Columns: user_id, title, description, due_date, due_time
$sql = "INSERT INTO reminders (user_id, title, description, due_date, due_time) 
        VALUES (?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if ($stmt) {
    // Parameter types: i (user_id), s (title), s (description), s (due_date), s (due_time/NULL)
    $param_types = "issss"; 
    
    // Bind parameters
    $stmt->bind_param($param_types, $user_id, $title, $description, $due_date, $due_time);

    if ($stmt->execute()) {
        $_SESSION['message'] = "New reminder added successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        error_log("Reminder insert failed: " . $stmt->error);
        $_SESSION['message'] = "Database error: Could not add reminder. SQL error: " . $stmt->error;
        $_SESSION['message_type'] = "danger";
    }
    $stmt->close();
} else {
    error_log("Database query preparation failed for add_reminder: " . $conn->error);
    $_SESSION['message'] = "A critical system error occurred.";
    $_SESSION['message_type'] = "danger";
}

$conn->close();

// 3. Redirect back to the schedule page for the date just added
header("Location: " . $base_redirect_path . "?date=" . $due_date);
exit();
?>