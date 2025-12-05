<?php
// CHRONONAV_WEB_DOSS/pages/admin/ocr_management_handler.php
// NOTE: This handler must be in the 'pages/admin/' directory as requested by the form action="ocr_management_handler.php"

session_start();
require_once '../../middleware/auth_check.php';
require_once '../../config/db_connect.php'; 
require_once '../../includes/functions.php';

requireRole(['admin']);
/** @var \mysqli $conn */

// Define the directory where uploaded template files will be stored
$upload_dir = '../../uploads/ocr_templates/';
$base_redirect_path = 'ocr_management.php'; // Redirect back to the current page

// Ensure the upload directory exists
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

if ($_SERVER["REQUEST_METHOD"] !== "POST" || ($_POST['action'] ?? '') !== 'upload_template') {
    header("Location: " . $base_redirect_path);
    exit();
}

$user_id = $_SESSION['user']['id'];
$template_name = trim($_POST['template_name'] ?? '');

// --- File Upload Handling ---
if (isset($_FILES['template_file']) && $_FILES['template_file']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['template_file'];
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Create a unique file name using user ID and timestamp
    $safe_file_name = preg_replace("/[^A-Za-z0-9_.]/", "", $template_name) . '_' . time() . '.' . $file_extension;
    $target_file = $upload_dir . $safe_file_name;

    // Move the file to the target directory
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        
        // 1. Prepare Data for Insertion
        $file_path_for_db = 'uploads/ocr_templates/' . $safe_file_name;
        $status = 'active'; // Assume active if upload succeeds

        // 2. Insert record into the ocr_templates table
        $sql = "INSERT INTO ocr_templates (template_name, file_path, status, created_at) VALUES (?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("sss", $template_name, $file_path_for_db, $status);
            
            if ($stmt->execute()) {
                $_SESSION['message'] = "Template <strong>" . htmlspecialchars($template_name) . "</strong> uploaded and saved successfully!";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "File uploaded, but database record failed: " . $stmt->error;
                $_SESSION['message_type'] = "danger";
                // Optionally delete file if DB insertion failed
                unlink($target_file);
            }
            $stmt->close();
        } else {
            $_SESSION['message'] = "Database error preparing insert statement.";
            $_SESSION['message_type'] = "danger";
        }

    } else {
        $_SESSION['message'] = "Failed to move uploaded file.";
        $_SESSION['message_type'] = "danger";
    }

} else {
    $_SESSION['message'] = "No file uploaded or file upload error occurred (Error Code: " . ($_FILES['template_file']['error'] ?? 'N/A') . ").";
    $_SESSION['message_type'] = "warning";
}

$conn->close();
header("Location: " . $base_redirect_path);
exit();
?>