<?php
// CHRONONAV_WEB_DOSS/includes/admin_feedback_handler.php

session_start();
require_once '../middleware/auth_check.php';
require_once '../config/db_connect.php';
require_once '../includes/functions.php';

// Ensure the user is logged in as admin
requireRole(['admin']); 
/** @var \mysqli $conn */

// --- Define the absolute base URL for redirection ---
$base_redirect_path = "/CHRONONAV_WEB_DOSS/pages/admin/view_profile.php";


if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    // Redirect if not a valid submission
    header("Location: " . $base_redirect_path);
    exit();
}

// 1. Sanitize and Validate Inputs
$user_id = $_SESSION['user']['id'];
$feedback_type = trim($_POST['feedback_type'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message_content = trim($_POST['message'] ?? '');
$rating = filter_var($_POST['rating'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 5]]);

// Default status is 'New' based on the feedback table schema.
$status = 'New'; 


// Simple required fields validation
if (empty($feedback_type) || empty($subject) || empty($message_content)) {
    $_SESSION['message'] = "Please fill in all required fields (Type, Subject, and Message).";
    $_SESSION['message_type'] = "danger";
    header("Location: " . $base_redirect_path);
    exit();
}

// Ensure rating is NULL if not set or invalid
if ($rating === false || $rating === null) {
    $rating = NULL;
}


// 2. Insert into the feedback table
// Columns in schema: feedback_id, user_id, feedback_type, subject, message, rating, status, submitted_at, created_at, updated_at
$sql = "INSERT INTO feedback 
        (user_id, feedback_type, subject, message, rating, status) 
        VALUES (?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if ($stmt) {
    // Parameter types: i (user_id), s (feedback_type), s (subject), s (message), i (rating/NULL), s (status)
    // NOTE: For rating, we use $rating. If rating is NULL, MySQLi handles binding NULL for 'i' if the column is nullable.
    // However, safest binding is to use "s" for nullable integers in mysqli. Since the column is INT(11) NULL, we will use 'i' for binding an integer, or pass NULL directly if $rating is NULL.
    
    // We adjust binding based on whether rating is present
    if ($rating !== NULL) {
        $param_types = "isssis"; 
        $params = [$user_id, $feedback_type, $subject, $message_content, $rating, $status];
    } else {
        // If rating is NULL, we skip binding it as 'i'. This requires a slight adjustment to SQL or safer binding.
        // The standard PHP mysqli binding is tricky with inline NULLs. The simplest approach for fixed columns is to always bind 6 variables/types, even if some are NULL, so we use 's' for the nullable rating.
        $rating_for_bind = (string) $rating; // Use string 'NULL' or '' for NULL handling if structure is an issue. Let's use 0/NULL based on schema.
        
        $param_types = "isssis"; 
        $params = [$user_id, $feedback_type, $subject, $message_content, $rating, $status];
    }

    // Since the rating column is INT(11) NULL, we must ensure we bind 6 variables with the type string.
    $param_types = "isssis"; // i, s, s, s, i, s (Assuming rating column is bound as 'i' when NULL is explicitly passed later if supported, or 's' if rating is missing)

    // Simpler, guaranteed method for fixed parameter counts:
    $param_types = "isssis";
    $rating_bind = $rating !== NULL ? $rating : 0; // Using 0 as a placeholder if NULL isn't natively supported by 'i' binding, though the column is nullable.
    
    // Bind 6 parameters
    $stmt->bind_param(
        $param_types, 
        $user_id, 
        $feedback_type, 
        $subject, 
        $message_content, 
        $rating_bind, // NOTE: If rating is NULL in the schema, using 's' for $rating_for_bind might be safer if you pass $rating.
        $status
    );

    // If using strict type binding with nullable integers (tricky in mysqli, so use s if 0 is not acceptable)
    // If we use 's' for rating, and pass NULL:
    $param_types = "isssss"; 
    $rating_for_bind = $rating !== NULL ? strval($rating) : NULL;
    $stmt->bind_param(
        $param_types, 
        $user_id, 
        $feedback_type, 
        $subject, 
        $message_content, 
        $rating_for_bind, 
        $status
    );
    
    // **********************************************
    // Final Safe Binding Strategy (using 's' for nullable int/text columns):
    $param_types = "isssss";
    $rating_bind = ($rating === NULL) ? null : strval($rating); // Explicitly pass NULL if needed
    
    $stmt->bind_param(
        $param_types, 
        $user_id, 
        $feedback_type, 
        $subject, 
        $message_content, 
        $rating_bind, 
        $status
    );
    // **********************************************


    if ($stmt->execute()) {
        $_SESSION['message'] = "Thank you! Your feedback has been submitted successfully.";
        $_SESSION['message_type'] = "success";
    } else {
        error_log("Feedback submission failed: " . $stmt->error);
        $_SESSION['message'] = "Database error: Could not submit feedback.";
        $_SESSION['message_type'] = "danger";
    }
    $stmt->close();
} else {
    error_log("Database query preparation failed for feedback: " . $conn->error);
    $_SESSION['message'] = "A critical system error occurred.";
    $_SESSION['message_type'] = "danger";
}

$conn->close();

// 3. Redirect back to the profile page
header("Location: " . $base_redirect_path);
exit();
?>