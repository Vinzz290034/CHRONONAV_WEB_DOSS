<?php
// CHRONONAV_WEB_DOSS/includes/admin_add_schedule_via_room_manager_handler.php

session_start();
require_once '../middleware/auth_check.php';
require_once '../config/db_connect.php';
require_once 'functions.php';

// Ensure the user has admin privileges
requireRole(['admin']); 
/** @var \mysqli $conn */

// --- Define the absolute base URL for redirection ---
$base_redirect_path = "/CHRONONAV_WEB_DOSS/pages/admin/room_manager.php";


if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST['create_schedule'])) {
    // Redirect if not a valid submission
    header("Location: " . $base_redirect_path);
    exit();
}

// 1. Sanitize and Validate Inputs
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? null);
$faculty_id = filter_var($_POST['faculty_id'] ?? null, FILTER_VALIDATE_INT);
$room_id = filter_var($_POST['room_id'] ?? null, FILTER_VALIDATE_INT);
$day_of_week = trim($_POST['day_of_week'] ?? '');
$start_time = trim($_POST['start_time'] ?? '');
$end_time = trim($_POST['end_time'] ?? '');

// Simple required fields validation
if (empty($title) || empty($day_of_week) || empty($start_time) || empty($end_time) || !$faculty_id) {
    $_SESSION['message'] = "Schedule Title, Faculty, Day, Start Time, and End Time are required.";
    $_SESSION['message_type'] = "danger";
    header("Location: " . $base_redirect_path);
    exit();
}

// Convert room_id to NULL if it's empty/unassigned, otherwise keep the integer
if (!$room_id) {
    $room_id = NULL;
}


// 2. Insert into the 'schedules' table
// SQL has 8 placeholders: user_id, faculty_id, room_id, title, description, day_of_week, start_time, end_time
$sql = "INSERT INTO schedules 
        (user_id, faculty_id, room_id, title, description, day_of_week, start_time, end_time) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if ($stmt) {
    // Use the faculty_id as the primary owner (user_id) for the schedule entry
    $schedule_owner_id = $faculty_id; 

    // FIX: The type string must be 8 characters long (3 'i's for IDs, 5 's's for strings)
    $param_types = "iiisssss"; 
    
    // Bind parameters: 8 variables being passed
    $stmt->bind_param(
        $param_types, 
        $schedule_owner_id, // 1. user_id (int)
        $faculty_id,        // 2. faculty_id (int)
        $room_id,           // 3. room_id (int/NULL)
        $title,             // 4. title (string)
        $description,       // 5. description (string)
        $day_of_week,       // 6. day_of_week (string)
        $start_time,        // 7. start_time (string)
        $end_time           // 8. end_time (string)
    );

    if ($stmt->execute()) {
        $_SESSION['message'] = "New schedule entry created and assigned successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        error_log("Schedule creation failed from Room Manager: " . $stmt->error);
        $_SESSION['message'] = "Database error: Could not create schedule. Error: " . $stmt->error;
        $_SESSION['message_type'] = "danger";
    }
    $stmt->close();
} else {
    error_log("Database query preparation failed for add_schedule_via_room_manager: " . $conn->error);
    $_SESSION['message'] = "A critical system error occurred.";
    $_SESSION['message_type'] = "danger";
}

$conn->close();

// 3. Redirect back to the Room Manager page
header("Location: " . $base_redirect_path);
exit();
?>