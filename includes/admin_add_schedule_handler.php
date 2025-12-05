<?php
// CHRONONAV_WEB_DOSS/includes/admin_add_schedule_handler.php

session_start();
require_once '../middleware/auth_check.php';
require_once '../config/db_connect.php';
require_once 'functions.php';

requireRole(['admin']); 
/** @var \mysqli $conn */

// --- Define the absolute base URL for redirection ---
// This prevents the "Not Found" error by ensuring the full path is always used.
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
$event_date = trim($_POST['event_date'] ?? '');
$start_time = trim($_POST['start_time'] ?? '');
$end_time = trim($_POST['end_time'] ?? '');
$room = trim($_POST['room'] ?? '');

$day_of_week = date('l', strtotime($event_date)); // e.g., Monday

// Simple validation
if (empty($title) || empty($event_date) || empty($start_time) || empty($end_time)) {
    $_SESSION['message'] = "Please fill in all required fields.";
    $_SESSION['message_type'] = "danger";
    // Redirect to the correct path with the date parameter
    header("Location: " . $base_redirect_path . "?date=" . $event_date);
    exit();
}

// 2. Insert into Database
// NOTE: THIS QUERY REQUIRES the 'room_name' column to exist in your 'schedules' table.
$sql = "INSERT INTO schedules (user_id, title, description, day_of_week, start_time, end_time, room_name) 
        VALUES (?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if ($stmt) {
    // Assuming room_name is used directly for now based on your form logic.
    $stmt->bind_param("issssss", $user_id, $title, $description, $day_of_week, $start_time, $end_time, $room);

    if ($stmt->execute()) {
        $_SESSION['message'] = "New schedule added successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        error_log("Schedule insert failed: " . $stmt->error);
        
        // Check for specific SQL errors (e.g., Unknown column)
        if (strpos($stmt->error, 'Unknown column') !== false) {
             $_SESSION['message'] = "Database error: Could not add schedule. The 'schedules' table is missing the 'room_name' column.";
        } else {
             $_SESSION['message'] = "Database error: Could not add schedule.";
        }

        $_SESSION['message_type'] = "danger";
    }
    $stmt->close();
} else {
    error_log("Database query preparation failed for add_schedule: " . $conn->error);
    $_SESSION['message'] = "A critical system error occurred.";
    $_SESSION['message_type'] = "danger";
}

$conn->close();

// 3. Redirect back to the schedule page for the date just added (FIXED PATH)
header("Location: " . $base_redirect_path . "?date=" . $event_date);
exit();
?>