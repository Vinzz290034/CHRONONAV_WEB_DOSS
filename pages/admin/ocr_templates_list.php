<?php
// CHRONONAV_WEB_DOSS/pages/admin/ocr_templates_list.php

session_start();
require_once '../../middleware/auth_check.php';
require_once '../../config/db_connect.php'; 
require_once '../../includes/functions.php';

requireRole(['admin']);

/** @var \mysqli $conn */ 

$user = $_SESSION['user'];
$page_title = "OCR Templates List";
$current_page = "ocr_management"; // Highlight the parent module in the sidebar

// Variables for header display (assumed available from session)
$display_username = htmlspecialchars($user['name'] ?? 'Admin');
$display_user_role = htmlspecialchars($user['role'] ?? 'Admin');
$profile_img_src = '../../uploads/profiles/default-avatar.png';
if (!empty($user['profile_img']) && file_exists('../../' . $user['profile_img'])) {
    $profile_img_src = '../../' . $user['profile_img'];
}

$message = '';
$message_type = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// --- Fetch OCR Templates (Using created table structure) ---
$templates = [];
$error = '';

// Assuming table ocr_templates columns: id, template_name, file_path, status, created_at
$stmt_templates = $conn->prepare("
    SELECT id, template_name, file_path, status, created_at
    FROM ocr_templates
    ORDER BY created_at DESC
");

if ($stmt_templates) {
    if ($stmt_templates->execute()) {
        $result_templates = $stmt_templates->get_result();
        while ($row = $result_templates->fetch_assoc()) {
            $templates[] = $row;
        }
    } else {
        $error = "Database execution failed: " . $stmt_templates->error;
        error_log("Template fetch execute failed: " . $stmt_templates->error);
    }
    $stmt_templates->close();
} else {
    $error = "Database query preparation failed: " . $conn->error;
    error_log("Template fetch prepare failed: " . $conn->error);
}

require_once '../../templates/admin/header_admin.php';
require_once '../../templates/admin/sidenav_admin.php';
?>

<style>
    /* CSS remains consistent */
    .main-content-wrapper {
        margin-left: 20%;
        padding: 2rem 2rem;
        font-family: "Space Grotesk", "Noto Sans", sans-serif;
    }
    .page-title {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 1.5rem;
    }
    .card-custom {
        border: 1px solid #e8edf3;
        border-radius: 0.75rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    }
    .card-header-custom {
        background-color: #f8fafb;
        border-bottom: 1px solid #e8edf3;
        font-weight: 600;
    }
    .btn-primary-custom {
        background-color: #2E78C6;
        color: white;
        border: none;
        border-radius: 0.5rem;
        padding: 0.5rem 1rem;
        font-weight: 500;
    }
    /* Table styles for templates */
    .table-custom thead th {
        background-color: #f0f2f5;
        font-weight: 600;
    }
    .table-custom tbody td {
        font-size: 0.875rem;
        vertical-align: middle;
    }
    .badge-success { background-color: #d1e7dd; color: #0f5132; }
    .badge-danger { background-color: #f8d7da; color: #721c24; }
    .badge-warning { background-color: #fff3cd; color: #664d03; }
</style>

<div class="main-content-wrapper">
    <h2 class="page-title">Manage OCR Templates</h2>

    <?php if ($message): ?>
        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="ocr_management.php" class="btn btn-secondary-custom">
            <i class="fas fa-arrow-left me-1"></i> Back to OCR Panel
        </a>
    </div>

    <div class="card card-custom mb-5">
        <div class="card-header card-header-custom">
            <h5 class="mb-0">Existing Templates (<?= count($templates) ?>)</h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($templates)): ?>
                <div class="alert alert-info text-center m-4">No OCR templates have been defined yet.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-custom table-striped mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Template Name</th>
                                <th>File Path</th>
                                <th>Created Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($templates as $template): ?>
                                <tr>
                                    <td><?= htmlspecialchars($template['id']) ?></td>
                                    <td><?= htmlspecialchars($template['template_name']) ?></td>
                                    <td><?= htmlspecialchars($template['file_path'] ?? 'N/A') ?></td>
                                    <td><?= date('Y-m-d H:i', strtotime($template['created_at'])) ?></td>
                                    <td>
                                        <span class="badge 
                                            <?php 
                                                $status = strtolower($template['status'] ?? 'draft');
                                                if ($status == 'active' || $status == 'processed') echo 'badge-success';
                                                elseif ($status == 'error') echo 'badge-danger';
                                                else echo 'badge-warning'; 
                                            ?>">
                                            <?= htmlspecialchars(ucfirst($template['status'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-info" onclick="alert('View details for <?= $template['template_name'] ?>')">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="confirm('Are you sure you want to delete this template?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
</div>

<?php require_once '../../templates/footer.php'; ?>