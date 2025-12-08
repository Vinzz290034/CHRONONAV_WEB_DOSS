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

<style>
    body {
        background-color: #ffffff;
        font-family: "Space Grotesk", "Noto Sans", sans-serif;
    }

    .layout-content-container {
        margin-left: 20%;
        padding: 20px 35px;
        min-height: 100vh;
        background-color: #ffffff;
    }

    /* Header styling */
    .text-dark.fw-bold.fs-3.mb-0 {
        font-size: 28px !important;
        font-weight: bold !important;
        color: #101518 !important;
        margin-bottom: 25px !important;
    }

    /* Card styling */
    .card {
        border: none;
        border-radius: 0.75rem;
        background: white;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        margin-bottom: 25px;
        overflow: hidden;
    }

    .card.shadow-sm {
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05) !important;
    }

    .card-header {
        background-color: #2e78c6;
        border-bottom: none;
        padding: 20px 25px;
        color: white;
        font-weight: 600;
        border-radius: 0.75rem 0.75rem 0 0 !important;
    }

    .card-header h5 {
        color: white;
        margin-bottom: 0;
        font-size: 18px;
        font-weight: 600;
    }

    .card-header h5 i {
        margin-right: 8px;
    }

    .card-body {
        padding: 25px;
    }

    .card-body.p-0 {
        padding: 0 !important;
    }

    /* Alert styling */
    .alert {
        border-radius: 0.75rem;
        border: none;
        padding: 16px 20px;
        margin-bottom: 20px;
    }

    .alert-info {
        background-color: #dbeafe;
        color: #1e40af;
    }

    .alert-success {
        background-color: #d1fae5;
        color: #065f46;
    }

    .alert-warning {
        background-color: #fef3c7;
        color: #92400e;
    }

    .alert-danger {
        background-color: #fee2e2;
        color: #991b1b;
    }

    /* Form styling */
    .form-label {
        font-size: 14px;
        font-weight: 500;
        color: #374151;
        margin-bottom: 6px;
    }

    .form-label.fw-medium {
        font-weight: 500;
    }

    .form-select,
    .form-select-sm {
        border-radius: 0.5rem;
        border: 1px solid #d1d5db;
        padding: 10px 12px;
        font-size: 14px;
        color: #101518;
        background-color: white;
        height: 40px;
    }

    .form-select:focus,
    .form-select-sm:focus {
        border-color: #2e78c6;
        box-shadow: 0 0 0 3px rgba(46, 120, 198, 0.1);
        outline: none;
    }

    /* Button styling */
    .btn {
        border-radius: 9999px;
        font-weight: 500;
        font-size: 0.875rem;
        padding: 10px 20px;
        border: none;
        transition: all 0.3s ease;
        height: 40px;
    }

    .btn-primary-custom {
        background-color: #2e78c6;
        color: white;
    }

    .btn-primary-custom:hover {
        background-color: #2563eb;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(46, 120, 198, 0.2);
    }

    .btn-light {
        background-color: #eaedf1;
        color: #101518;
    }

    .btn-light:hover {
        background-color: #dce8f3;
        transform: translateY(-1px);
    }

    .btn i {
        margin-right: 8px;
    }

    /* Table styling */
    .table-responsive {
        border-radius: 0.5rem;
        overflow: hidden;
        border: 1px solid #e5e7eb;
    }

    .table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        margin-bottom: 0;
    }

    .table-hover tbody tr {
        transition: background-color 0.2s ease;
    }

    .table-hover tbody tr:hover {
        background-color: #f9fafb;
    }

    .table th {
        background-color: #eaedf1;
        color: #101518;
        font-weight: 600;
        font-size: 14px;
        padding: 16px 12px;
        border-bottom: 2px solid #d1d5db;
    }

    .table td {
        padding: 14px 12px;
        font-size: 14px;
        color: #374151;
        border-bottom: 1px solid #f1f3f4;
        vertical-align: middle;
    }

    .table tbody tr:last-child td {
        border-bottom: none;
    }

    /* Badge styling */
    .badge {
        font-size: 0.75rem;
        font-weight: 500;
        padding: 6px 12px;
        border-radius: 9999px;
        white-space: nowrap;
    }

    .bg-danger {
        background-color: #dc3545 !important;
        color: white;
    }

    .bg-info {
        background-color: #17a2b8 !important;
        color: white;
    }

    .bg-success {
        background-color: #28a745 !important;
        color: white;
    }

    .bg-secondary {
        background-color: #6c757d !important;
        color: white;
    }

    .bg-warning {
        background-color: #ffc107 !important;
        color: #000;
    }

    /* Text utilities */
    .text-truncate-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .d-inline-block.text-truncate {
        max-width: 250px;
        display: inline-block;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    /* Flex utilities */
    .d-flex.flex-wrap.gap-3 {
        gap: 20px;
    }

    .flex-grow-1 {
        flex-grow: 1;
        min-width: 150px;
    }

    .justify-content-end {
        justify-content: flex-end;
    }

    /* Floating button */
    .floating-btn {
        background-color: #2e78c6;
        color: white;
        border: none;
        border-radius: 0.75rem;
        padding: 12px 20px;
        font-weight: 500;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .floating-btn:hover {
        background-color: #2563eb;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(46, 120, 198, 0.2);
    }

    /* Scrollbar styling */
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

    /* Responsive styles */
    @media (max-width: 767px) {
        .layout-content-container {
            margin-left: 0;
            padding: 15px;
        }

        .text-dark.fw-bold.fs-3.mb-0 {
            font-size: 22px !important;
            margin-bottom: 20px !important;
        }

        .card-header {
            padding: 15px 20px;
        }

        .card-header h5 {
            font-size: 16px;
        }

        .card-body {
            padding: 20px;
        }

        .d-flex.flex-wrap.align-items-end.gap-3 {
            flex-direction: column;
            gap: 15px;
            align-items: stretch;
        }

        .flex-grow-1 {
            width: 100%;
        }

        .btn-primary-custom,
        .btn-light {
            width: 100%;
            justify-content: center;
        }

        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .table th,
        .table td {
            padding: 12px 8px;
            font-size: 13px;
            white-space: nowrap;
        }

        .badge {
            font-size: 0.7rem;
            padding: 4px 8px;
        }

        .d-inline-block.text-truncate {
            max-width: 150px;
        }

        .floating-btn {
            width: 100%;
            justify-content: center;
            margin-top: 15px;
        }
    }

    @media (min-width: 768px) and (max-width: 1023px) {
        .layout-content-container {
            margin-left: 80px;
            padding: 20px 25px;
        }

        .text-dark.fw-bold.fs-3.mb-0 {
            font-size: 24px !important;
        }

        .card-header h5 {
            font-size: 17px;
        }

        .table th,
        .table td {
            padding: 14px 10px;
            font-size: 13.5px;
        }
    }

    @media (min-width: 1024px) {
        .layout-content-container {
            margin-left: 20%;
            padding: 20px 35px;
        }
    }

    /* Additional utility classes */
    .mb-3 {
        margin-bottom: 20px !important;
    }

    .mb-4 {
        margin-bottom: 25px !important;
    }

    .pt-3 {
        padding-top: 20px;
    }

    .p-0 {
        padding: 0 !important;
    }

    .px-5 {
        padding-left: 35px !important;
        padding-right: 35px !important;
    }

    .fs-3 {
        font-size: 28px !important;
    }

    .fw-bold {
        font-weight: 700 !important;
    }

    .fw-medium {
        font-weight: 500 !important;
    }

    /* Icon styling */
    .fas {
        font-size: 16px;
    }

    .me-2 {
        margin-right: 8px;
    }

    /* Print button */
    .floating-btn {
        background-color: #2e78c6;
        color: white;
        border: none;
        border-radius: 0.75rem;
        padding: 12px 20px;
        font-weight: 500;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .floating-btn:hover {
        background-color: #2563eb;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(46, 120, 198, 0.2);
    }




    /* ====================================================================== */
/* Dark Mode Overrides for Audit Logs Page                                */
/* ====================================================================== */
body.dark-mode {
    background-color: #121A21 !important;
    color: #E5E8EB !important;
}

body.dark-mode .layout-content-container {
    background-color: #121A21 !important;
    color: #E5E8EB !important;
}

/* Header styling */
body.dark-mode .text-dark.fw-bold.fs-3.mb-0 {
    color: #E5E8EB !important;
}

/* Card styling */
body.dark-mode .card {
    background-color: #263645 !important;
    border: 1px solid #121A21 !important;
    color: #E5E8EB !important;
}

body.dark-mode .card.shadow-sm {
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15) !important;
}

/* Card header */
body.dark-mode .card-header {
    background-color: #1C7DD6 !important;
    color: #FFFFFF !important;
    border-bottom: 1px solid #121A21 !important;
}

body.dark-mode .card-header h5 {
    color: #FFFFFF !important;
}

/* Alert styling */
body.dark-mode .alert {
    background-color: #1a2635 !important;
    border: 1px solid #263645 !important;
}

body.dark-mode .alert-info {
    background-color: rgba(28, 125, 214, 0.15) !important;
    color: #94ADC7 !important;
    border-color: #1C7DD6 !important;
}

body.dark-mode .alert-success {
    background-color: rgba(40, 167, 69, 0.15) !important;
    color: #94ADC7 !important;
    border-color: #28a745 !important;
}

body.dark-mode .alert-warning {
    background-color: rgba(255, 193, 7, 0.15) !important;
    color: #94ADC7 !important;
    border-color: #ffc107 !important;
}

body.dark-mode .alert-danger {
    background-color: rgba(220, 53, 69, 0.15) !important;
    color: #94ADC7 !important;
    border-color: #dc3545 !important;
}

/* Form styling */
body.dark-mode .form-label {
    color: #E5E8EB !important;
}

body.dark-mode .form-select,
body.dark-mode .form-select-sm {
    background-color: #263645 !important;
    border: 1px solid #121A21 !important;
    color: #E5E8EB !important;
}

body.dark-mode .form-select:focus,
body.dark-mode .form-select-sm:focus {
    background-color: #263645 !important;
    border-color: #1C7DD6 !important;
    box-shadow: 0 0 0 3px rgba(28, 125, 214, 0.25) !important;
    color: #E5E8EB !important;
}

/* Button styling */
body.dark-mode .btn-primary-custom {
    background-color: #1C7DD6 !important;
    color: #FFFFFF !important;
    border: 1px solid #1C7DD6 !important;
}

body.dark-mode .btn-primary-custom:hover {
    background-color: #2E78C6 !important;
    border-color: #2E78C6 !important;
}

body.dark-mode .btn-light {
    background-color: #263645 !important;
    color: #94ADC7 !important;
    border: 1px solid #121A21 !important;
}

body.dark-mode .btn-light:hover {
    background-color: #1C7DD6 !important;
    color: #FFFFFF !important;
    border-color: #1C7DD6 !important;
}

/* Table styling */
body.dark-mode .table-responsive {
    border: 1px solid #121A21 !important;
}

body.dark-mode .table {
    background-color: #263645 !important;
}

body.dark-mode .table th {
    background-color: #121A21 !important;
    color: #E5E8EB !important;
    border-bottom: 2px solid #263645 !important;
}

body.dark-mode .table td {
    color: #E5E8EB !important;
    border-bottom: 1px solid #121A21 !important;
    background-color: #263645 !important;
}

body.dark-mode .table-hover tbody tr:hover {
    background-color: #1a2635 !important;
}

body.dark-mode .table-hover tbody tr:hover td {
    background-color: #1a2635 !important;
}

/* Badge styling */
body.dark-mode .bg-danger {
    background-color: #dc3545 !important;
    color: #FFFFFF !important;
}

body.dark-mode .bg-info {
    background-color: #17a2b8 !important;
    color: #FFFFFF !important;
}

body.dark-mode .bg-success {
    background-color: #28a745 !important;
    color: #FFFFFF !important;
}

body.dark-mode .bg-secondary {
    background-color: #6c757d !important;
    color: #FFFFFF !important;
}

body.dark-mode .bg-warning {
    background-color: #ffc107 !important;
    color: #000000 !important;
}

/* Floating button */
body.dark-mode .floating-btn {
    background-color: #1C7DD6 !important;
    color: #FFFFFF !important;
    border: 1px solid #1C7DD6 !important;
}

body.dark-mode .floating-btn:hover {
    background-color: #2E78C6 !important;
    border-color: #2E78C6 !important;
}

/* Scrollbar styling for dark mode */
body.dark-mode ::-webkit-scrollbar {
    width: 12px;
    height: 12px;
}

body.dark-mode ::-webkit-scrollbar-track {
    background: #121A21 !important;
}

body.dark-mode ::-webkit-scrollbar-thumb {
    background-color: #263645 !important;
    border-radius: 6px;
    border: 3px solid #121A21 !important;
}

body.dark-mode ::-webkit-scrollbar-thumb:hover {
    background-color: #1C7DD6 !important;
}

/* Close button for alerts */
body.dark-mode .btn-close {
    filter: invert(1) grayscale(100%) brightness(200%) !important;
}

/* Form dropdown options */
body.dark-mode .form-select option {
    background-color: #263645 !important;
    color: #E5E8EB !important;
}

body.dark-mode .form-select option:hover,
body.dark-mode .form-select option:focus {
    background-color: #1C7DD6 !important;
    color: #FFFFFF !important;
}

/* Table text truncation */
body.dark-mode .d-inline-block.text-truncate {
    color: #E5E8EB !important;
}

/* No data message */
body.dark-mode .alert-info.text-center.m-4 {
    background-color: rgba(28, 125, 214, 0.1) !important;
    color: #94ADC7 !important;
    border: 1px solid rgba(28, 125, 214, 0.3) !important;
}

/* Responsive adjustments */
@media (max-width: 767px) {
    body.dark-mode .layout-content-container {
        background-color: #121A21 !important;
    }
}

@media (min-width: 768px) and (max-width: 1023px) {
    body.dark-mode .layout-content-container {
        background-color: #121A21 !important;
    }
}

@media (min-width: 1024px) {
    body.dark-mode .layout-content-container {
        background-color: #121A21 !important;
    }
}

/* Print view adjustments for dark mode */
@media print {
    body.dark-mode {
        background-color: white !important;
        color: black !important;
    }
    
    body.dark-mode .card,
    body.dark-mode .table,
    body.dark-mode .table th,
    body.dark-mode .table td {
        background-color: white !important;
        color: black !important;
        border-color: #ccc !important;
    }
}

/* Modal styling (if any modals are used) */
body.dark-mode .modal-content {
    background-color: #263645 !important;
    border: 1px solid #121A21 !important;
    color: #E5E8EB !important;
}

body.dark-mode .modal-header {
    background-color: #121A21 !important;
    border-bottom: 1px solid #263645 !important;
}

body.dark-mode .modal-header .modal-title {
    color: #E5E8EB !important;
}

body.dark-mode .modal-header .btn-close {
    filter: invert(1) grayscale(100%) brightness(200%) !important;
}
</style>

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
                                <option value="<?= htmlspecialchars($role) ?>" <?= ($role_filter === $role) ? 'selected' : '' ?>>
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
                                <option value="<?= htmlspecialchars($action) ?>" <?= ($action_filter === $action) ? 'selected' : '' ?>>
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
                    <div class="alert alert-info text-center m-4">No audit logs found matching the current filter criteria.
                    </div>
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
                                                    case 'admin':
                                                        echo 'bg-danger';
                                                        break;
                                                    case 'faculty':
                                                        echo 'bg-info';
                                                        break;
                                                    case 'user':
                                                        echo 'bg-success';
                                                        break;
                                                    default:
                                                        echo 'bg-secondary';
                                                        break;
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

    <!-- JQuery Library -->
    <script src="../../assets/js/jquery.min.js"></script>

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

<script>
    document.body.style.backgroundColor = "#ffffff";
</script>