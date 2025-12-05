<?php
// CHRONONAV_WEB_DOSS/pages/admin/audit_logs.php
session_start();
require_once '../../middleware/auth_check.php';
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

requireRole(['admin']);

/** @var \mysqli $conn */ 

$user = $_SESSION['user'];
$current_user_id = $user['id'];

// --- Fetch fresh admin data for display in header and profile sections ---
$stmt_admin = $conn->prepare("SELECT name, role, profile_img FROM users WHERE id = ?");
if ($stmt_admin) {
    $stmt_admin->bind_param("i", $current_user_id);
    $stmt_admin->execute();
    $result_admin = $stmt_admin->get_result();
    if ($result_admin->num_rows > 0) {
        $admin_data = $result_admin->fetch_assoc();
        $_SESSION['user'] = array_merge($_SESSION['user'], $admin_data);
        $display_username = htmlspecialchars($admin_data['name'] ?? 'Admin');
        $display_user_role = htmlspecialchars(ucfirst($admin_data['role'] ?? 'Admin'));
        $profile_img_src = (strpos($admin_data['profile_img'] ?? '', 'uploads/') === 0) ? '../../' . htmlspecialchars($admin_data['profile_img']) : '../../uploads/profiles/default-avatar.png';
    } else {
        error_log("Security Alert: Admin User ID {$current_user_id} in session not found in database for audit_logs.");
        session_destroy();
        header('Location: ../../auth/login.php?error=user_not_found');
        exit();
    }
    $stmt_admin->close();
} else {
    error_log("Database query preparation failed for admin profile in audit_logs: " . $conn->error);
    $display_username = 'Admin User';
    $display_user_role = 'Admin';
    $profile_img_src = '../../uploads/profiles/default-avatar.png';
}


$page_title = "System Audit Logs";
$current_page = "audit_logs";

$message = '';
$message_type = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// --- Filtering Logic (Dynamic) ---
$role_filter = $_GET['role_filter'] ?? '';
$action_filter = $_GET['action_filter'] ?? '';
$where_clauses = [];
$bind_params = [];
$bind_types = '';

if (!empty($role_filter)) {
    $where_clauses[] = "u.role = ?";
    $bind_params[] = $role_filter;
    $bind_types .= 's';
}

if (!empty($action_filter)) {
    $where_clauses[] = "al.action = ?";
    $bind_params[] = $action_filter;
    $bind_types .= 's';
}

$where_sql = count($where_clauses) > 0 ? " WHERE " . implode(" AND ", $where_clauses) : "";


// --- Fetch Unique Roles and Actions for Filter Dropdowns ---
$available_roles = [];
$result_roles = $conn->query("SELECT DISTINCT role FROM users ORDER BY role ASC");
if ($result_roles) {
    while ($row = $result_roles->fetch_assoc()) {
        $available_roles[] = $row['role'];
    }
}

$available_actions = [];
$result_actions = $conn->query("SELECT DISTINCT action FROM audit_log ORDER BY action ASC");
if ($result_actions) {
    while ($row = $result_actions->fetch_assoc()) {
        $available_actions[] = $row['action'];
    }
}


// --- Audit Log Fetching Logic (Modified to include filtering) ---
$audit_logs = [];
$error = '';
$sql_logs = "
    SELECT al.id, al.user_id, al.action, al.timestamp, al.details,
           u.name AS user_name, u.role AS user_role
    FROM audit_log al
    JOIN users u ON al.user_id = u.id
    {$where_sql}
    ORDER BY al.timestamp DESC
    LIMIT 500
";

$stmt_logs = $conn->prepare($sql_logs);

if ($stmt_logs) {
    // Dynamically bind parameters if filters are present
    if (!empty($bind_params)) {
        $bind_names = array($bind_types);
        foreach ($bind_params as $key => $value) {
            $bind_names[] = &$bind_params[$key];
        }
        call_user_func_array([$stmt_logs, 'bind_param'], $bind_names);
    }
    
    $stmt_logs->execute();
    $result_logs = $stmt_logs->get_result();
    while ($row = $result_logs->fetch_assoc()) {
        $audit_logs[] = $row;
    }
    $stmt_logs->close();
} else {
    $error = "Failed to load audit logs due to a database error: " . $conn->error;
    error_log("Failed to prepare audit logs query: " . $conn->error);
}

// Variables for header (re-set after main data fetch)
$display_username = htmlspecialchars($user['name'] ?? 'Admin');
$display_user_role = htmlspecialchars($user['role'] ?? 'Admin');
$profile_img_src_header = '../../uploads/profiles/default-avatar.png';
if (!empty($user['profile_img']) && file_exists('../../' . $user['profile_img'])) {
    $profile_img_src_header = '../../' . $user['profile_img'];
}

require_once '../../templates/admin/header_admin.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'ChronoNav - Audit Logs' ?></title>


    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">


    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">


    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>
    <link rel="stylesheet" as="style" onload="this.rel='stylesheet'"
        href="https://fonts.googleapis.com/css2?display=swap&family=Noto+Sans:wght@400;500;700;900&family=Space+Grotesk:wght@400;500;700">


    <link rel="icon" type="image/x-icon"
        href="https://res.cloudinary.com/deua2yipj/image/upload/v1758917007/ChronoNav_logo_muon27.png">

    <style>
        body {
            font-family: "Space Grotesk", "Noto Sans", sans-serif;
            background-color: #fff;
        }

        /* Basic Layout Styles */
        .layout-content-container {
            max-width: 80%;
            flex: 1;
            margin: 0 auto;
            margin-left: 20%;
        }


        .main-dashboard-content {
            padding: 2rem 1rem;
        }


        .card {
            border: none;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        /* Card Header Styles */
        .card-header {
            background-color: #2E78C6;
            border-radius: 0.75rem 0.75rem 0 0 !important;
            border: none;
            padding: 1rem 1.5rem;
        }

        .card-header h5 {
            color: #fff;
        }

        /* Table Styles */
        .table {
            margin-bottom: 0;
        }

        .table th {
            border-top: none;
            font-weight: 600;
            color: #0d151c;
            background-color: #f8fafc;
            padding: 1rem 0.75rem;
        }

        .table td {
            padding: 0.75rem;
            vertical-align: middle;
            border-color: #e7edf4;
        }

        /* Badge Colors */
        .badge {
            font-size: 0.75rem;
            font-weight: 500;
            padding: 0.375rem 0.75rem;
            border-radius: 0.5rem;
            white-space: nowrap;
        }

        .bg-danger { background-color: #dc3545 !important; }
        .bg-info { background-color: #0dcaf0 !important; }
        .bg-success { background-color: #198754 !important; }
        .bg-secondary { background-color: #6c757d !important; }
        .bg-warning { background-color: #ffc107 !important; color: #000; }

        /* Button Styles */
        .btn-primary-custom {
            background-color: #2E78C6;
            color: white;
            font-weight: bold;
            border-radius: 0.5rem;
            padding: 0.5rem 1rem;
            border: none;
            height: 38px;
        }
        .btn-light {
            background-color: #f0f2f5;
            font-weight: bold;
            border-radius: 0.5rem;
            padding: 0.5rem 1rem;
            border: none;
            height: 38px;
        }
        
        /* Layout and Typography */
        .text-truncate-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* Scrollbar Styling */
        ::-webkit-scrollbar { width: 12px; height: 12px; }
        ::-webkit-scrollbar-track { background: #ffffff; }
        ::-webkit-scrollbar-thumb { background-color: #737373; border-radius: 6px; border: 3px solid #ffffff; }
        ::-webkit-scrollbar-thumb:hover { background-color: #2e78c6; }
        
        /* Responsive styles */
        @media (max-width: 767px) {
            .layout-content-container { max-width: 100% !important; margin-left: 0 !important; }
            .table-responsive { font-size: 0.875rem; }
            .badge { font-size: 0.7rem; padding: 0.25rem 0.5rem; }
            .d-flex.flex-wrap.gap-3 { flex-direction: column !important; align-items: stretch; }
            .card-body { padding: 1rem; }
            .btn-primary-custom, .btn-light { width: 100%; margin-bottom: 0.5rem; }
            .form-select-sm { font-size: 0.8rem !important; height: 38px !important; }
        }

        @media (min-width: 768px) and (max-width: 1023px) {
            .layout-content-container { max-width: 85% !important; margin-left: 15% !important; }
            .form-select-sm { font-size: 0.85rem !important; }
        }

        @media (min-width: 1024px) {
            .layout-content-container { max-width: 80% !important; margin-left: 20% !important; }
        }
    </style>
</head>

<body>
    <?php require_once '../../templates/admin/sidenav_admin.php'; ?>

    <div class="layout-content-container d-flex flex-column p-3 px-5 justify-content-end">

        <div class="d-flex flex-wrap justify-content-between gap-3 mb-3">
            <p class="text-dark fw-bold fs-3 mb-0" style="min-width: 288px;">System Audit Logs</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm mb-4">
            <div class="card-header text-white">
                <h5 class="mb-0 fw-bold"><i class="fas fa-search me-2"></i>Filter & Export Logs</h5>
            </div>
            <div class="card-body">
                <form action="audit_logs.php" method="GET" class="d-flex flex-wrap align-items-end gap-3 mb-3">
                    
                    <div class="flex-grow-1" style="min-width: 150px;">
                        <label for="role_filter" class="form-label fw-medium">Filter by Role</label>
                        <select name="role_filter" id="role_filter" class="form-select form-select-sm">
                            <option value="">-- All Roles --</option>
                            <?php foreach ($available_roles as $role): ?>
                                <option value="<?= htmlspecialchars($role) ?>"
                                    <?= ($role_filter === $role) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars(ucfirst($role)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="flex-grow-1" style="min-width: 150px;">
                        <label for="action_filter" class="form-label fw-medium">Filter by Action</label>
                        <select name="action_filter" id="action_filter" class="form-select form-select-sm">
                            <option value="">-- All Actions --</option>
                            <?php foreach ($available_actions as $action): ?>
                                <option value="<?= htmlspecialchars($action) ?>"
                                    <?= ($action_filter === $action) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($action) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary-custom">
                        <i class="fas fa-filter"></i> Apply Filter
                    </button>
                    
                    <?php if (!empty($role_filter) || !empty($action_filter)): ?>
                        <a href="audit_logs.php" class="btn btn-light">
                            <i class="fas fa-times"></i> Reset
                        </a>
                    <?php endif; ?>
                </form>

                <button type="button" class="btn btn-primary-custom" onclick="exportLogs()">
                    <i class="fas fa-file-export"></i> Export Filtered Logs (CSV)
                </button>
            </div>
        </div>
        <div class="card shadow-sm mb-4">
            <div class="card-header text-white">
                <h5 class="mb-0 fw-bold">Recent Audit Activities (<?= count($audit_logs) ?> entries)</h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($audit_logs)): ?>
                    <div class="alert alert-info text-center m-4">No audit logs found matching the current filter criteria.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Timestamp</th>
                                    <th>User</th>
                                    <th>Role</th>
                                    <th>Action</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($audit_logs as $log): ?>
                                    <tr>
                                        <td class="fw-medium"><?= htmlspecialchars($log['id']) ?></td>
                                        <td><?= date('Y-m-d H:i:s', strtotime($log['timestamp'])) ?></td>
                                        <td><?= htmlspecialchars($log['user_name'] ?? 'N/A') ?></td>
                                        <td>
                                            <span class="badge 
                                                <?php
                                                switch (htmlspecialchars($log['user_role'])) {
                                                    case 'admin': echo 'bg-danger'; break;
                                                    case 'faculty': echo 'bg-info'; break;
                                                    case 'user': echo 'bg-success'; break;
                                                    default: echo 'bg-secondary'; break;
                                                }
                                                ?>">
                                                <?= htmlspecialchars(ucfirst($log['user_role'] ?? 'Unknown')) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="fw-medium"><?= htmlspecialchars($log['action']) ?></span>
                                        </td>
                                        <td>
                                            <span class="d-inline-block text-truncate" style="max-width: 250px;"
                                                title="<?= htmlspecialchars($log['details']) ?>">
                                                <?= htmlspecialchars($log['details']) ?>
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


        <div class="d-flex justify-content-end overflow-hidden p-0 pt-3">
            <button class="floating-btn fw-bold text-white" onclick="window.print()">
                <i class="fas fa-print"></i>
                <span class="d-none d-md-inline">Print View</span>
            </button>
        </div>
    </div>

    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Add hover effects to table rows
            const tableRows = document.querySelectorAll('.table tbody tr');
            tableRows.forEach(row => {
                row.addEventListener('mouseenter', function () {
                    this.style.transform = 'translateY(-2px)';
                    this.style.transition = 'transform 0.2s ease';
                });

                row.addEventListener('mouseleave', function () {
                    this.style.transform = 'translateY(0)';
                });
            });

            // Auto-hide alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });


        /**
         * Function to export the audit logs, capturing current filter values.
         */
        function exportLogs() {
            const role = document.getElementById('role_filter').value;
            const action = document.getElementById('action_filter').value;
            
            // Construct the URL to the export handler, passing current filters
            let exportUrl = '../../includes/export_audit_logs_handler.php?export=1';

            if (role) {
                exportUrl += '&role_filter=' + encodeURIComponent(role);
            }
            if (action) {
                exportUrl += '&action_filter=' + encodeURIComponent(action);
            }
            
            // Redirecting the window triggers the file download
            window.location.href = exportUrl;
        }
    </script>

    <?php require_once '../../templates/footer.php'; ?>
</body>

</html>