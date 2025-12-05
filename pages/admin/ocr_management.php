<?php
// CHRONONAV_WEB_DOSS/pages/admin/ocr_management.php

session_start();
require_once '../../middleware/auth_check.php';
require_once '../../config/db_connect.php'; 
require_once '../../includes/functions.php';

requireRole(['admin']);

/** @var \mysqli $conn */

$user = $_SESSION['user'];

// --- Variables for Header and Sidenav ---
$page_title = "OCR Management Panel";
$current_page = "ocr_management";

// Variables for header display (retrieval logic omitted for brevity, assumed functional)
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

// --- Fetch Log Data (Finalized Query) ---
$scan_logs = [];
$error = '';

// FIX: Using al.title (aliased as file_name for display consistency) and al.is_active (for status)
$stmt_logs = $conn->prepare("
    SELECT al.id, al.title AS file_name, al.is_active, al.created_at AS timestamp,
           u.name AS user_name
    FROM add_pdf al
    JOIN users u ON al.user_id = u.id  /* Assuming users.id is the primary key */
    ORDER BY al.created_at DESC 
    LIMIT 20
"); 

if ($stmt_logs) {
    
    if ($stmt_logs === false) {
        $error = "Failed to prepare scan logs query (Internal Error).";
        error_log("Failed to prepare scan logs query: " . $conn->error);
    } else {
        $stmt_logs->execute();
        $result_logs = $stmt_logs->get_result();
        
        if ($result_logs === false) {
            $error = "Failed to execute log query: " . $stmt_logs->error;
            error_log("Execute failed: " . $stmt_logs->error);
        } else {
            while ($row = $result_logs->fetch_assoc()) {
                $scan_logs[] = $row;
            }
        }
        $stmt_logs->close();
    }
} else {
    $error = "Failed to initialize log query: " . $conn->error;
    error_log("Failed to initialize audit logs query: " . $conn->error);
}

require_once '../../templates/admin/header_admin.php';
require_once '../../templates/admin/sidenav_admin.php';
?>

<style>
    /* CSS styles remain unchanged */
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

    .section-title {
        font-size: 1.5rem;
        font-weight: 600;
        margin-top: 2rem;
        margin-bottom: 1rem;
        color: #2E78C6;
    }

    .card-custom {
        border: 1px solid #e8edf3;
        border-radius: 0.75rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    }

    .card-header-custom {
        background-color: #f8fafb;
        border-bottom: 1px solid #e8edf1;
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
    .btn-secondary-custom {
        background-color: #6c757d;
        color: white;
        border: none;
        border-radius: 0.5rem;
        padding: 0.5rem 1rem;
        font-weight: 500;
    }
    .form-control-custom {
        background-color: #f8fafb;
        border: 1px solid #d1dce6;
        border-radius: 0.5rem;
        padding: 0.75rem;
    }

    /* Table styles for logs */
    .table-custom thead th {
        background-color: #f0f2f5;
    }
    .table-custom tbody td {
        font-size: 0.875rem;
    }
</style>

<div class="main-content-wrapper">
    <h2 class="page-title">OCR Management Panel</h2>

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

    <div class="card card-custom mb-5">
        <div class="card-header card-header-custom">
            <h5 class="mb-0">⚙️ Manage OCR Templates</h5>
        </div>
        <div class="card-body">
            <p class="text-muted">Use this section to upload new template documents (PDF/Image) to train the OCR engine or define new data fields.</p>
            
            <form action="ocr_management_handler.php" method="POST" enctype="multipart/form-data" class="row g-3">
                <input type="hidden" name="action" value="upload_template">

                <div class="col-md-6">
                    <label for="template_name" class="form-label">Template Name (e.g., Schedule-V2)</label>
                    <input type="text" class="form-control form-control-custom" id="template_name" name="template_name" required>
                </div>
                
                <div class="col-md-6">
                    <label for="template_file" class="form-label">Upload Template File (PDF/Image)</label>
                    <input type="file" class="form-control form-control-custom" id="template_file" name="template_file" accept=".pdf,.jpg,.png" required>
                </div>
                
                <div class="col-12">
                    <button type="submit" class="btn btn-primary-custom">
                        <i class="fas fa-upload me-1"></i> Upload & Process Template
                    </button>
                    <a href="ocr_templates_list.php" class="btn btn-secondary-custom ms-2">
                        <i class="fas fa-list-alt me-1"></i> View Existing Templates
                    </a>
                </div>
            </form>
        </div>
    </div>

    <h3 class="section-title">📊 View Recent Scan Logs</h3>
    <div class="card card-custom mb-5">
        <div class="card-body p-0">
            <?php if (empty($scan_logs)): ?>
                <div class="alert alert-info text-center m-4">No recent scan logs found.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-custom table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Log ID</th>
                                <th>Timestamp</th>
                                <th>User</th>
                                <th>Schedule Title</th>
                                <th>Status (Active)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($scan_logs as $log): ?>
                                <tr>
                                    <td><?= htmlspecialchars($log['id']) ?></td>
                                    <td><?= date('Y-m-d H:i:s', strtotime($log['timestamp'])) ?></td>
                                    <td><?= htmlspecialchars($log['user_name'] ?? 'System') ?></td>
                                    <td><?= htmlspecialchars($log['file_name'] ?? 'N/A') ?></td> 
                                    <td>
                                        <span class="badge 
                                            <?php 
                                                // Using the is_active (tinyint) column for status display
                                                if ($log['is_active'] == 1) {
                                                    echo 'bg-success';
                                                } else {
                                                    echo 'bg-danger';
                                                }
                                            ?>">
                                            <?= $log['is_active'] == 1 ? 'Active' : 'Inactive' ?>
                                        </span>
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