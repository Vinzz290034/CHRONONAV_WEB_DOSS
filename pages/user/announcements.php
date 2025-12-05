<?php
// CHRONONAV_WEBZ/pages/user/announcements.php

require_once '../../middleware/auth_check.php';
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php'; // Assuming functions.php exists for common functions and requireRole()

// Ensure only logged-in users (or admins who can view user pages) can access this page
requireRole(['user', 'admin']); // Allows both users and admins to view this page.

$user = $_SESSION['user'];
$user_role = $user['role'] ?? 'guest'; // Get user role
$user_id = $user['id'] ?? null;

// --- Fetch fresh user data for display in header and profile sections ---
// This is crucial for the profile picture and name in the header dropdown
$stmt_user_data = $conn->prepare("SELECT name, email, profile_img FROM users WHERE id = ?");
if ($stmt_user_data) {
    $stmt_user_data->bind_param("i", $user_id);
    $stmt_user_data->execute();
    $result_user_data = $stmt_user_data->get_result();
    if ($result_user_data->num_rows > 0) {
        $user_from_db = $result_user_data->fetch_assoc();
        $_SESSION['user'] = array_merge($_SESSION['user'], $user_from_db); // Update session with fresh data
        $user = $_SESSION['user']; // Use the updated $user array for display
    } else {
        // Handle case where user might have been deleted from DB but session persists
        error_log("Security Alert: User ID {$user_id} in session not found in database for announcements (user).");
        session_destroy();
        header('Location: ../../auth/login.php?error=user_not_found');
        exit();
    }
    $stmt_user_data->close();
} else {
    error_log("Database query preparation failed for announcements (user): " . $conn->error);
    // Optionally redirect or show a user-friendly error
}

// Prepare variables for header display
$display_username = htmlspecialchars($user['name'] ?? 'Guest');
$display_user_role = htmlspecialchars(ucfirst($user['role'] ?? 'User'));

// Determine the correct profile image source path for the header
$display_profile_img = htmlspecialchars($user['profile_img'] ?? 'uploads/profiles/default-avatar.png');
$profile_img_src = (strpos($display_profile_img, 'uploads/') === 0) ? '../../' . $display_profile_img : $display_profile_img;


$page_title = "Campus Announcements"; // Changed title to be more user-facing
$current_page = "announcements";

$message = '';
$message_type = '';

// Variables for the announcement form (for both create and edit) - only relevant if admin can manage here
$announcement_id_to_edit = null;
$announcement_title_form = '';
$announcement_content_form = '';
$form_action_text = 'Publish Announcement';

// --- Handle Announcement Deletion (Only for Admin) ---
if ($user_role === 'admin' && isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id']) && is_numeric($_GET['id'])) {
    $announcement_id = (int) $_GET['id'];

    $stmt = $conn->prepare("DELETE FROM announcements WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $announcement_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Announcement deleted successfully!";
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = "Error deleting announcement: " . $stmt->error;
            $_SESSION['message_type'] = 'danger';
        }
        $stmt->close();
    } else {
        $_SESSION['message'] = "Database error preparing delete: " . $conn->error;
        $_SESSION['message_type'] = 'danger';
    }
    header("Location: announcements.php"); // Redirect to clear GET parameters
    exit();
}

// --- Handle Announcement Editing (Load Data into Form) (Only for Admin) ---
if ($user_role === 'admin' && isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id']) && is_numeric($_GET['id'])) {
    $announcement_id_to_edit = (int) $_GET['id'];
    $form_action_text = 'Update Announcement';

    // Fetch existing announcement data
    $stmt = $conn->prepare("SELECT title, content FROM announcements WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $announcement_id_to_edit);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $announcement_data = $result->fetch_assoc();
            $announcement_title_form = htmlspecialchars($announcement_data['title']);
            $announcement_content_form = htmlspecialchars($announcement_data['content']);
        } else {
            $_SESSION['message'] = "Announcement not found.";
            $_SESSION['message_type'] = 'danger';
            header("Location: announcements.php"); // Redirect if ID is invalid
            exit();
        }
        $stmt->close();
    } else {
        $_SESSION['message'] = "Database error fetching announcement for edit: " . $conn->error;
        $_SESSION['message_type'] = 'danger';
        header("Location: announcements.php");
        exit();
    }
}


// --- Handle Form Submission (New Announcement Creation or Update) (Only for Admin) ---
if ($user_role === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_announcement'])) {
    $title = trim($_POST['announcement_title'] ?? '');
    $content = trim($_POST['announcement_content'] ?? '');
    $submitted_announcement_id = $_POST['announcement_id'] ?? null; // Hidden field for ID if updating

    if (empty($title) || empty($content)) {
        $message = "Please fill in both the title and content for the announcement.";
        $message_type = 'danger';
    } else {
        if ($submitted_announcement_id && is_numeric($submitted_announcement_id)) {
            // It's an UPDATE operation
            $stmt = $conn->prepare("UPDATE announcements SET title = ?, content = ?, updated_at = NOW() WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("ssi", $title, $content, $submitted_announcement_id);
                if ($stmt->execute()) {
                    $_SESSION['message'] = "Announcement updated successfully!";
                    $_SESSION['message_type'] = 'success';
                } else {
                    $_SESSION['message'] = "Error updating announcement: " . $stmt->error;
                    $_SESSION['message_type'] = 'danger';
                }
                $stmt->close();
            } else {
                $_SESSION['message'] = "Database error preparing update: " . $conn->error;
                $_SESSION['message_type'] = 'danger';
            }
        } else {
            // It's an INSERT (Create New) operation
            $stmt = $conn->prepare("INSERT INTO announcements (user_id, title, content) VALUES (?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("iss", $user_id, $title, $content);
                if ($stmt->execute()) {
                    $_SESSION['message'] = "Announcement posted successfully!";
                    $_SESSION['message_type'] = 'success';
                } else {
                    $_SESSION['message'] = "Error posting announcement: " . $stmt->error;
                    $_SESSION['message_type'] = 'danger';
                }
                $stmt->close();
            } else {
                $_SESSION['message'] = "Database error preparing announcement: " . $conn->error;
                $_SESSION['message_type'] = 'danger';
            }
        }
        // Redirect after POST to prevent resubmission on refresh
        header("Location: announcements.php");
        exit();
    }
}

// --- Fetch All Announcements (for displaying) ---
$announcements = [];
$stmt_announcements = $conn->prepare("SELECT a.*, u.name as posted_by_name FROM announcements a JOIN users u ON a.user_id = u.id ORDER BY a.published_at DESC");
if ($stmt_announcements) {
    $stmt_announcements->execute();
    $result_announcements = $stmt_announcements->get_result();
    while ($row = $result_announcements->fetch_assoc()) {
        $announcements[] = $row;
    }
    $stmt_announcements->close();
} else {
    // This error will only show if fetching fails initially
    $message = "Error fetching announcements: " . $conn->error;
    $message_type = 'danger';
}

// Check for and display session messages after all processing
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message']); // Clear the message after displaying
    unset($_SESSION['message_type']);
}

?>

<?php
// Include the user-specific header
require_once '../../templates/user/header_user.php';
?>

<?php
// Include the user-specific sidebar (sidenav)
require_once '../../templates/user/sidenav_user.php';
?>

<style>
    body {
        font-family: "Space Grotesk", "Noto Sans", sans-serif;
        background-color: rgb(255, 255, 255);
    }

    .main-dashboard-content {
        margin-left: 20%;
        padding: 20px 35px;
    }

    .main-dashboard-content-wrapper {
        font-family: "Space Grotesk", "Noto Sans", sans-serif;
        min-height: 100vh;
        padding-top: 20px;
    }

    .announcement-board-header h1 {
        color: #0e151b;
        font-size: 28px;
        margin-bottom: 1.5rem;
    }

    .card {
        background-color: #ffffff;
        border: none;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        margin-bottom: 2rem;
    }

    .card-title {
        color: #0e151b;
        font-size: 22px;
        font-weight: 700;
        letter-spacing: -0.015em;
        margin: 0;
    }

    .btn-primary {
        background-color: #1d7dd7;
        border-color: #1d7dd7;
        color: #f8fafb;
        font-weight: 600;
        letter-spacing: 0.015em;
        padding: 0.5rem 1rem;
    }

    .btn-primary:hover {
        background-color: #1a6fc0;
        border-color: #1a6fc0;
    }

    .btn-secondary {
        background-color: #6c757d;
        border-color: #6c757d;
        color: #f8fafb;
        font-weight: 600;
        letter-spacing: 0.015em;
        padding: 0.5rem 1rem;
    }

    .btn-warning {
        background-color: #ffc107;
        border-color: #ffc107;
        color: #000000;
        font-weight: 600;
        letter-spacing: 0.015em;
        padding: 0.5rem 1rem;
    }

    .btn-danger {
        background-color: #dc3545;
        border-color: #dc3545;
        color: #f8fafb;
        font-weight: 600;
        letter-spacing: 0.015em;
        padding: 0.5rem 1rem;
    }

    .btn-sm {
        height: 32px;
        padding: 0.375rem 0.75rem;
        font-size: 0.875rem;
    }

    .alert {
        border: none;
        border-radius: 8px;
        padding: 1rem 1.25rem;
        margin-bottom: 1rem;
    }

    .alert-info {
        background-color: #cff4fc;
        color: #055160;
    }

    .alert-success {
        background-color: #d1e7dd;
        color: #0f5132;
    }

    .alert-danger {
        background-color: #f8d7da;
        color: #721c24;
    }

    .alert-warning {
        background-color: #fff3cd;
        color: #664d03;
    }

    .form-control {
        background-color: #f8fafb;
        border-color: #d1dce6;
        color: #0e151b;
        padding: 0.75rem;
    }

    .form-control:focus {
        box-shadow: none;
        border-color: #d1dce6;
    }

    .form-label {
        color: #0e151b;
        font-weight: 500;
        margin-bottom: 0.5rem;
    }

    .badge {
        font-size: 0.75rem;
        font-weight: 600;
        padding: 0.375rem 0.75rem;
        border-radius: 0.375rem;
    }

    .bg-info {
        background-color: #0dcaf0 !important;
    }

    .bg-secondary {
        background-color: #6c757d !important;
    }

    .announcement-item {
        background-color: #f8fafb;
        border: 1px solid #d1dce6;
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 1rem;
    }

    .announcement-item h3 {
        color: #0e151b;
        font-size: 1.25rem;
        font-weight: 600;
        margin-bottom: 1rem;
    }

    .announcement-content p {
        color: #0e151b;
        line-height: 1.6;
    }

    .announcement-meta {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
        margin-bottom: 1rem;
    }

    .announcement-actions {
        display: flex;
        gap: 0.5rem;
        margin-top: 1rem;
    }

    .img-fluid {
        border-radius: 8px;
        margin-bottom: 1rem;
    }

    /* Scrollbar Styling */
    ::-webkit-scrollbar {
        width: 12px;
        height: 12px;
    }

    ::-webkit-scrollbar-track {
        background: #ffffff;
    }

    ::-webkit-scrollbar-thumb {
        background-color: #737373;
        border-radius: 6px;
        border: 3px solid #ffffff;
    }

    ::-webkit-scrollbar-thumb:hover {
        background-color: #2e78c6;
    }

    @media (max-width: 768px) {
        .main-dashboard-content {
            margin-left: 0;
            padding: 15px;
        }

        .main-dashboard-content-wrapper {
            padding-top: 0;
        }

        .announcement-board-header h1 {
            font-size: 22px;
        }

        .card-title {
            font-size: 18px;
        }

        .announcement-section {
            padding: 1rem !important;
        }

        .announcement-item {
            padding: 1rem;
        }

        .announcement-meta {
            flex-direction: column;
            gap: 0.25rem;
        }

        .announcement-actions {
            flex-direction: column;
            gap: 0.5rem;
        }

        .announcement-actions .btn {
            width: 100%;
        }
    }



    /* ====================================================================== */
    /* Dark Mode Overrides for Announcements Page - Custom Colors            */
    /* ====================================================================== */
    body.dark-mode {
        background-color: #121A21 !important;
        /* Primary dark background */
        color: #E5E8EB !important;
    }

    /* Main content containers */
    body.dark-mode .main-dashboard-content-wrapper {
        background-color: #121A21 !important;
    }

    body.dark-mode .main-dashboard-content {
        background-color: #121A21 !important;
        color: #E5E8EB !important;
    }

    body.dark-mode .user-support-container {
        background-color: #121A21 !important;
    }

    /* Header and titles */
    body.dark-mode .announcement-board-header h2 {
        color: #E5E8EB !important;
        /* Light text for page title */
    }

    body.dark-mode .card-title {
        color: #E5E8EB !important;
        /* Light text for card titles */
    }

    /* Cards and sections */
    body.dark-mode .card {
        background-color: #263645 !important;
        /* Secondary dark background */
        border: 1px solid #121A21 !important;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3) !important;
    }

    body.dark-mode .announcement-section {
        background-color: #263645 !important;
        border: 1px solid #121A21 !important;
    }

    /* Announcement items */
    body.dark-mode .announcement-item {
        background-color: #121A21 !important;
        /* Primary dark background */
        border: 1px solid #263645 !important;
        /* Secondary border */
    }

    body.dark-mode .announcement-item h3 {
        color: #E5E8EB !important;
        /* Light text for announcement titles */
    }

    body.dark-mode .announcement-content p {
        color: #E5E8EB !important;
        /* Light text for content */
    }

    /* Badges */
    body.dark-mode .badge.bg-info {
        background-color: #0D47A1 !important;
        /* Dark blue for info badges */
        color: #BBDEFB !important;
        /* Light blue text */
    }

    body.dark-mode .badge.bg-secondary {
        background-color: #424242 !important;
        /* Dark gray for secondary badges */
        color: #E0E0E0 !important;
        /* Light gray text */
    }

    /* Buttons */
    body.dark-mode .btn-primary {
        background-color: #1C7DD6 !important;
        /* Active blue */
        border-color: #1C7DD6 !important;
        color: #FFFFFF !important;
        /* White text */
    }

    body.dark-mode .btn-primary:hover {
        background-color: #1565C0 !important;
        /* Darker blue on hover */
        border-color: #1565C0 !important;
    }

    body.dark-mode .btn-secondary {
        background-color: #263645 !important;
        /* Secondary dark */
        border-color: #121A21 !important;
        /* Primary border */
        color: #94ADC7 !important;
        /* Secondary text */
    }

    body.dark-mode .btn-secondary:hover {
        background-color: #121A21 !important;
        /* Primary dark on hover */
        color: #E5E8EB !important;
        /* Light text on hover */
    }

    body.dark-mode .btn-warning {
        background-color: #F57C00 !important;
        /* Dark orange */
        border-color: #F57C00 !important;
        color: #FFE0B2 !important;
        /* Light orange text */
    }

    body.dark-mode .btn-warning:hover {
        background-color: #EF6C00 !important;
        /* Darker orange on hover */
        border-color: #EF6C00 !important;
        color: #FFFFFF !important;
    }

    body.dark-mode .btn-danger {
        background-color: #C62828 !important;
        /* Dark red */
        border-color: #C62828 !important;
        color: #FFCDD2 !important;
        /* Light red text */
    }

    body.dark-mode .btn-danger:hover {
        background-color: #B71C1C !important;
        /* Darker red on hover */
        border-color: #B71C1C !important;
        color: #FFFFFF !important;
    }

    /* Form elements */
    body.dark-mode .form-control {
        background-color: #121A21 !important;
        /* Primary dark */
        border: 1px solid #263645 !important;
        /* Secondary border */
        color: #E5E8EB !important;
        /* Light text */
    }

    body.dark-mode .form-control:focus {
        background-color: #121A21 !important;
        border-color: #1C7DD6 !important;
        /* Blue focus */
        color: #E5E8EB !important;
        box-shadow: 0 0 0 2px rgba(28, 125, 214, 0.2) !important;
    }

    body.dark-mode .form-control::placeholder {
        color: #94ADC7 !important;
        /* Secondary text for placeholder */
    }

    body.dark-mode .form-label {
        color: #94ADC7 !important;
        /* Secondary text for labels */
    }

    /* Alerts */
    body.dark-mode .alert {
        background-color: #263645 !important;
        /* Secondary dark background */
        border: 1px solid #121A21 !important;
    }

    body.dark-mode .alert-info {
        background-color: #0D47A1 !important;
        /* Dark blue */
        color: #BBDEFB !important;
        /* Light blue text */
        border-color: #1565C0 !important;
    }

    body.dark-mode .alert-success {
        background-color: #1B5E20 !important;
        /* Dark green */
        color: #C8E6C9 !important;
        /* Light green text */
        border-color: #2E7D32 !important;
    }

    body.dark-mode .alert-danger {
        background-color: #B71C1C !important;
        /* Dark red */
        color: #FFCDD2 !important;
        /* Light red text */
        border-color: #C62828 !important;
    }

    body.dark-mode .alert-warning {
        background-color: #F57C00 !important;
        /* Dark orange */
        color: #FFE0B2 !important;
        /* Light orange text */
        border-color: #EF6C00 !important;
    }

    /* Scrollbar for dark mode */
    body.dark-mode ::-webkit-scrollbar-track {
        background: #121A21 !important;
        /* Primary dark track */
    }

    body.dark-mode ::-webkit-scrollbar-thumb {
        background-color: #263645 !important;
        /* Secondary dark thumb */
        border: 3px solid #121A21 !important;
    }

    body.dark-mode ::-webkit-scrollbar-thumb:hover {
        background-color: #1C7DD6 !important;
        /* Blue on hover */
    }

    /* Icons */
    body.dark-mode .fa-bullhorn,
    body.dark-mode .fa-clipboard-list,
    body.dark-mode .fa-calendar-alt,
    body.dark-mode .fa-user-tie,
    body.dark-mode .fa-paper-plane,
    body.dark-mode .fa-times,
    body.dark-mode .fa-edit,
    body.dark-mode .fa-trash-alt {
        color: #94ADC7 !important;
        /* Secondary color for icons */
    }

    body.dark-mode .btn:hover .fa-bullhorn,
    body.dark-mode .btn:hover .fa-clipboard-list,
    body.dark-mode .btn:hover .fa-paper-plane,
    body.dark-mode .btn:hover .fa-times,
    body.dark-mode .btn:hover .fa-edit,
    body.dark-mode .btn:hover .fa-trash-alt {
        color: #FFFFFF !important;
        /* White icons on hover */
    }

    /* Announcement actions */
    body.dark-mode .announcement-actions .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
    }

    /* Announcement images */
    body.dark-mode .img-fluid {
        border: 1px solid #263645 !important;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    /* Cancel button specific */
    body.dark-mode a[href="announcements.php"] .btn-secondary:hover .fa-times {
        color: #FFFFFF !important;
    }

    /* Create/Edit form specific */
    body.dark-mode .announcement-form {
        background-color: transparent !important;
    }

    /* Responsive adjustments for dark mode */
    @media (max-width: 768px) {
        body.dark-mode .main-dashboard-content {
            background-color: #121A21 !important;
        }

        body.dark-mode .announcement-item {
            background-color: #121A21 !important;
        }

        body.dark-mode .announcement-section {
            background-color: #263645 !important;
        }
    }

    /* Meta information text */
    body.dark-mode .announcement-meta .badge {
        background-color: #121A21 !important;
        /* Primary dark for meta badges */
        border: 1px solid #263645 !important;
    }

    /* Text colors */
    body.dark-mode .text-dark {
        color: #E5E8EB !important;
        /* Light text instead of dark */
    }

    body.dark-mode .text-muted {
        color: #94ADC7 !important;
        /* Secondary text instead of muted */
    }

    /* Close button in alerts */
    body.dark-mode .btn-close {
        filter: invert(1) grayscale(100%) brightness(200%) !important;
    }

    /* Empty state */
    body.dark-mode .alert-info.mb-0 {
        background-color: #0D47A1 !important;
        color: #BBDEFB !important;
    }

    /* Hover effects for announcement items */
    body.dark-mode .announcement-item:hover {
        border-color: #1C7DD6 !important;
        /* Blue border on hover */
        box-shadow: 0 4px 12px rgba(28, 125, 214, 0.1) !important;
    }

    /* Action buttons container */
    body.dark-mode .announcement-actions {
        border-top: 1px solid #263645 !important;
        /* Dark border above actions */
        padding-top: 1rem;
    }

    /* Confirmation dialog (if using custom confirm styles) */
    body.dark-mode .confirm-dialog {
        background-color: #263645 !important;
        color: #E5E8EB !important;
        border: 1px solid #121A21 !important;
    }

    /* Focus states for accessibility */
    body.dark-mode .btn:focus,
    body.dark-mode .form-control:focus,
    body.dark-mode a:focus {
        outline: 2px solid #1C7DD6 !important;
        outline-offset: 2px;
    }

    /* Loading states */
    body.dark-mode .loading {
        background-color: rgba(18, 26, 33, 0.9) !important;
        color: #E5E8EB !important;
    }

    /* Selection text */
    body.dark-mode ::selection {
        background-color: #1C7DD6 !important;
        /* Blue selection */
        color: #FFFFFF !important;
    }

    body.dark-mode ::-moz-selection {
        background-color: #1C7DD6 !important;
        color: #FFFFFF !important;
    }
</style>

<!-- Favicon -->
<link rel="icon" type="image/x-icon"
    href="https://res.cloudinary.com/deua2yipj/image/upload/v1758917007/ChronoNav_logo_muon27.png">

<?php include('../../includes/semantics/head.php'); ?>

<div class="main-dashboard-content-wrapper">
    <div class="main-dashboard-content">
        <!-- Header Section -->
        <div class="announcement-board-header px-3 pt-4 pb-2">
            <h2 class="fs-3 fw-bold"><?= $page_title ?></h2>
        </div>

        <?php if ($message): // Display session message ?>
            <div class="alert alert-<?= $message_type ?> alert-dismissible fade show mx-3" role="alert">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="user-support-container">
            <?php if ($user_role === 'admin'): // Only show announcement creation/edit form for admins ?>
                <div class="announcement-section card p-4 mb-4 shadow-sm">
                    <h2 class="card-title mb-4 d-flex align-items-center gap-2">
                        <i class="fas fa-bullhorn"></i>
                        <?= ($announcement_id_to_edit) ? 'Edit Announcement' : 'Create New Announcement' ?>
                    </h2>
                    <form action="announcements.php" method="POST" class="announcement-form">
                        <?php if ($announcement_id_to_edit): // Hidden field for ID when editing ?>
                            <input type="hidden" name="announcement_id"
                                value="<?= htmlspecialchars($announcement_id_to_edit) ?>">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label for="announcement_title" class="form-label">Title:</label>
                            <input type="text" id="announcement_title" name="announcement_title" class="form-control"
                                placeholder="e.g., Important Schedule Change"
                                value="<?= htmlspecialchars($announcement_title_form) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="announcement_content" class="form-label">Content:</label>
                            <textarea id="announcement_content" name="announcement_content" class="form-control" rows="8"
                                placeholder="Write your announcement details here..."
                                required><?= htmlspecialchars($announcement_content_form) ?></textarea>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" name="submit_announcement"
                                class="btn btn-primary d-flex align-items-center gap-2">
                                <i class="fas fa-paper-plane"></i> <?= $form_action_text ?>
                            </button>
                            <?php if ($announcement_id_to_edit): // Add a cancel button for edit mode ?>
                                <a href="announcements.php" class="btn btn-secondary ms-2 d-flex align-items-center gap-2">
                                    <i class="fas fa-times"></i> Cancel Edit
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <div class="announcement-section card p-4 shadow-sm">
                <h2 class="card-title mb-4 d-flex align-items-center gap-2">
                    <i class="fas fa-clipboard-list"></i> All Announcements
                </h2>
                <?php if (!empty($announcements)): ?>
                    <div class="announcement-list">
                        <?php foreach ($announcements as $announcement): ?>
                            <div class="announcement-item mb-3">
                                <div class="announcement-meta">
                                    <span class="badge bg-info text-dark d-inline-flex align-items-center gap-1">
                                        <i class="fas fa-calendar-alt"></i>
                                        <?= date('F j, Y, g:i a', strtotime($announcement['published_at'])) ?>
                                    </span>
                                    <span class="badge bg-secondary d-inline-flex align-items-center gap-1">
                                        <i class="fas fa-user-tie"></i> Posted by:
                                        <?= htmlspecialchars($announcement['posted_by_name']) ?>
                                    </span>
                                </div>
                                <h3><?= htmlspecialchars($announcement['title']) ?></h3>
                                <div class="announcement-content">
                                    <?php if (!empty($announcement['image_path'])): ?>
                                        <img src="../../<?= htmlspecialchars($announcement['image_path']) ?>"
                                            alt="Announcement Image" class="img-fluid">
                                    <?php endif; ?>
                                    <p><?= nl2br(htmlspecialchars($announcement['content'])) ?></p>
                                </div>
                                <?php if ($user_role === 'admin'): ?>
                                    <div class="announcement-actions">
                                        <a href="announcements.php?action=edit&id=<?= htmlspecialchars($announcement['id']) ?>"
                                            class="btn btn-warning btn-sm d-flex align-items-center gap-1">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="announcements.php?action=delete&id=<?= htmlspecialchars($announcement['id']) ?>"
                                            class="btn btn-danger btn-sm d-flex align-items-center gap-1"
                                            onclick="return confirm('Are you sure you want to delete this announcement? This action cannot be undone.');">
                                            <i class="fas fa-trash-alt"></i> Delete
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mb-0">No announcements available at the moment.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../templates/common/onboarding_modal.php'; ?>

<!-- Bootstrap JS Bundle with Popper -->

<?php include_once '../../templates/footer.php'; ?>
<script src="../../assets/js/jquery.min.js"></script>
<script src="../../assets/js/script.js"></script>
<script src="../../assets/js/onboarding_tour.js"></script>

<?php include('../../includes/semantics/footer.php'); ?>