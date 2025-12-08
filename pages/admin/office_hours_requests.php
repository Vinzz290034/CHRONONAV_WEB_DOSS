<?php
// CHRONONAV_WEB_DOSS/pages/admin/office_hours_requests.php

require_once '../../middleware/auth_check.php';
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';
require_once '../../backend/admin/office_hours_requests_logic.php';

/** @var \mysqli $conn */ //
requireRole(['admin']);

$user_id = $_SESSION['user']['id'];
$stmt = $conn->prepare("SELECT name, role, profile_img FROM users WHERE id = ?");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $header_user_data = $result->fetch_assoc();
        $display_username = htmlspecialchars($header_user_data['name'] ?? 'Admin User');
        $display_user_role = htmlspecialchars(ucfirst($header_user_data['role'] ?? 'Admin'));
        $profile_img_src = (strpos($header_user_data['profile_img'], 'uploads/') === 0) ? '../../' . $header_user_data['profile_img'] : '../../uploads/profiles/default-avatar.png';
    } else {
        $display_username = 'Admin User';
        $display_user_role = 'Admin';
        $profile_img_src = '../../uploads/profiles/default-avatar.png';
    }
    $stmt->close();
} else {
    $display_username = 'Admin User';
    $display_user_role = 'Admin';
    $profile_img_src = '../../uploads/profiles/default-avatar.png';
}

$page_title = "Office Hours Requests";
$current_page = "office_hours_requests";

$message = $_SESSION['message'] ?? '';
$message_type = $_SESSION['message_type'] ?? '';
unset($_SESSION['message']);
unset($_SESSION['message_type']);

require_once '../../templates/admin/header_admin.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'ChronoNav - Admin' ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>
    <link rel="stylesheet" as="style" onload="this.rel='stylesheet'"
        href="https://fonts.googleapis.com/css2?display=swap&family=Inter:wght@400;500;700;900&family=Noto+Sans:wght@400;500;700;900">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon"
        href="https://res.cloudinary.com/deua2yipj/image/upload/v1758917007/ChronoNav_logo_muon27.png">

    <style>
        body {
            font-family: "Space Grotesk", "Noto Sans", sans-serif;
            background-color: #ffffff;
            min-height: 100vh;
        }

        .layout-container {
            min-height: 100vh;
        }

        .layout-content-container {
            flex: 1;
            margin-left: 20%;
        }

        .page-title {
            color: #0e151b;
            font-size: 32px;
            font-weight: 700;
            letter-spacing: -0.015em;
        }

        .section-title {
            color: #0e151b;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.015em;
            border-bottom: 1px solid #d1dce6;
        }

        .form-label {
            color: #0e151b;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .form-control,
        .form-control:focus {
            background-color: #f8fafb;
            border-color: #d1dce6;
            color: #0e151b;
            height: 56px;
            padding: 15px;
        }

        .form-control:focus {
            box-shadow: none;
            border-color: #d1dce6;
        }

        .form-control::placeholder {
            color: #507495;
        }

        .input-group-text {
            background-color: #f8fafb;
            border-color: #d1dce6;
            color: #507495;
            border-left: 0;
        }

        .input-group .form-control {
            border-right: 0;
        }

        textarea.form-control {
            min-height: 144px;
            resize: none;
        }

        .btn-primary {
            background-color: #1d7dd7;
            border-color: #1d7dd7;
            color: #f8fafb;
            font-weight: 700;
            letter-spacing: 0.015em;
            padding: 8px 16px;
            height: 40px;
        }

        .btn-primary:hover {
            background-color: #1a6fc0;
            border-color: #1a6fc0;
        }

        .btn-success {
            background-color: #198754;
            border-color: #198754;
            color: #f8fafb;
            font-weight: 700;
            letter-spacing: 0.015em;
            padding: 8px 16px;
            height: 40px;
        }

        .btn-info {
            background-color: #0dcaf0;
            border-color: #0dcaf0;
            color: #000000;
            font-weight: 700;
            letter-spacing: 0.015em;
            padding: 8px 16px;
            height: 40px;
        }

        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
            color: #f8fafb;
            font-weight: 700;
            letter-spacing: 0.015em;
            padding: 8px 16px;
            height: 40px;
        }

        .info-text {
            color: #0e151b;
        }

        .empty-state-text {
            background-color: #cff4fc;
            color: #055160;
            font-size: 14px;
            padding: 1rem;
            border-radius: 8px;
        }

        .card {
            background-color: #ffffff;
            border: none;
            border-radius: 8px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .alert {
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .table {
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
        }

        .table th {
            background-color: #f8fafb;
            color: #0e151b;
            font-weight: 600;
            border-bottom: 1px solid #d1dce6;
        }

        .table td {
            border-bottom: 1px solid #f1f1f1;
            color: #0e151b;
        }

        .badge {
            font-weight: 500;
            padding: 0.5rem 1rem;
        }

        .btn-sm {
            height: 32px;
            padding: 4px 12px;
            font-size: 0.875rem;
        }

        .btn-group-action {
            white-space: nowrap;
        }

        .btn-group-action .btn {
            margin-right: 0.5rem;
        }

        .btn-group-action .btn:last-child {
            margin-right: 0;
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
            .layout-content-container {
                margin-left: 0;
            }

            .btn-group-action {
                white-space: normal;
            }

            .btn-group-action .btn {
                margin-bottom: 0.5rem;
                display: block;
                width: 100%;
            }
        }



        /* ====================================================================== */
        /* Dark Mode Overrides for Office Hours Requests Page                     */
        /* ====================================================================== */
        body.dark-mode {
            background-color: #121A21 !important;
            color: #E5E8EB !important;
        }

        body.dark-mode .layout-container {
            background-color: #121A21 !important;
        }

        body.dark-mode .layout-content-container {
            background-color: #121A21 !important;
        }

        /* Page titles */
        body.dark-mode .page-title {
            color: #E5E8EB !important;
        }

        body.dark-mode .section-title {
            color: #E5E8EB !important;
            border-bottom: 1px solid #263645 !important;
        }

        /* Cards */
        body.dark-mode .card {
            background-color: #263645 !important;
            border: 1px solid #121A21 !important;
            color: #E5E8EB !important;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2) !important;
        }

        /* Form labels and inputs */
        body.dark-mode .form-label {
            color: #E5E8EB !important;
        }

        body.dark-mode .form-control,
        body.dark-mode .form-select,
        body.dark-mode textarea.form-control {
            background-color: #121A21 !important;
            border: 1px solid #263645 !important;
            color: #E5E8EB !important;
        }

        body.dark-mode .form-control:focus,
        body.dark-mode .form-select:focus,
        body.dark-mode textarea.form-control:focus {
            background-color: #121A21 !important;
            border-color: #1C7DD6 !important;
            color: #E5E8EB !important;
            box-shadow: 0 0 0 0.2rem rgba(28, 125, 214, 0.25) !important;
        }

        body.dark-mode .form-control::placeholder {
            color: #94ADC7 !important;
        }

        /* Input group */
        body.dark-mode .input-group-text {
            background-color: #121A21 !important;
            border: 1px solid #263645 !important;
            color: #94ADC7 !important;
        }

        /* Buttons */
        body.dark-mode .btn-primary {
            background-color: #1C7DD6 !important;
            border-color: #1C7DD6 !important;
            color: #FFFFFF !important;
        }

        body.dark-mode .btn-primary:hover {
            background-color: #1a6fc0 !important;
            border-color: #1a6fc0 !important;
        }

        body.dark-mode .btn-success {
            background-color: #28a745 !important;
            border-color: #28a745 !important;
            color: #ffffff !important;
        }

        body.dark-mode .btn-success:hover {
            background-color: #218838 !important;
            border-color: #218838 !important;
        }

        body.dark-mode .btn-info {
            background-color: #0dcaf0 !important;
            border-color: #0dcaf0 !important;
            color: #000000 !important;
        }

        body.dark-mode .btn-info:hover {
            background-color: #31d2f2 !important;
            border-color: #31d2f2 !important;
            color: #000000 !important;
        }

        body.dark-mode .btn-danger {
            background-color: #dc3545 !important;
            border-color: #dc3545 !important;
            color: #ffffff !important;
        }

        body.dark-mode .btn-danger:hover {
            background-color: #bb2d3b !important;
            border-color: #bb2d3b !important;
        }

        body.dark-mode .btn-secondary {
            background-color: #263645 !important;
            border-color: #263645 !important;
            color: #94ADC7 !important;
        }

        body.dark-mode .btn-secondary:hover {
            background-color: #121A21 !important;
            border-color: #121A21 !important;
            color: #E5E8EB !important;
        }

        /* Text colors */
        body.dark-mode .info-text {
            color: #E5E8EB !important;
        }

        body.dark-mode .empty-state-text {
            background-color: #0D47A1 !important;
            color: #BBDEFB !important;
            border: 1px solid #1565C0 !important;
        }

        body.dark-mode .text-muted {
            color: #94ADC7 !important;
        }

        /* Table styling */
        body.dark-mode .table {
            background-color: #263645 !important;
            color: #E5E8EB !important;
        }

        body.dark-mode .table th {
            background-color: #121A21 !important;
            color: #E5E8EB !important;
            border-bottom: 1px solid #263645 !important;
        }

        body.dark-mode .table td {
            color: #E5E8EB !important;
            border-bottom: 1px solid #121A21 !important;
        }

        body.dark-mode .table-hover tbody tr:hover {
            background-color: rgba(28, 125, 214, 0.1) !important;
        }

        /* Badges */
        body.dark-mode .badge {
            font-weight: 500;
            padding: 0.5rem 1rem;
        }

        body.dark-mode .badge.bg-success {
            background-color: #1B5E20 !important;
            color: #C8E6C9 !important;
            border: 1px solid #2E7D32 !important;
        }

        body.dark-mode .badge.bg-warning {
            background-color: #E65100 !important;
            color: #FFECB3 !important;
            border: 1px solid #F57C00 !important;
        }

        body.dark-mode .badge.bg-info {
            background-color: #0D47A1 !important;
            color: #BBDEFB !important;
            border: 1px solid #1565C0 !important;
        }

        body.dark-mode .badge.bg-danger {
            background-color: #B71C1C !important;
            color: #FFCDD2 !important;
            border: 1px solid #C62828 !important;
        }

        /* Alerts */
        body.dark-mode .alert {
            background-color: #263645 !important;
            border: 1px solid #121A21 !important;
            color: #E5E8EB !important;
        }

        body.dark-mode .alert-success {
            background-color: #1B5E20 !important;
            color: #C8E6C9 !important;
            border-color: #2E7D32 !important;
        }

        body.dark-mode .alert-warning {
            background-color: #E65100 !important;
            color: #FFECB3 !important;
            border-color: #F57C00 !important;
        }

        body.dark-mode .alert-danger {
            background-color: #B71C1C !important;
            color: #FFCDD2 !important;
            border-color: #C62828 !important;
        }

        body.dark-mode .alert-info {
            background-color: #0D47A1 !important;
            color: #BBDEFB !important;
            border-color: #1565C0 !important;
        }

        /* Modal styling */
        body.dark-mode .modal-content {
            background-color: #263645 !important;
            border: 1px solid #121A21 !important;
            color: #E5E8EB !important;
        }

        body.dark-mode .modal-header {
            background-color: #121A21 !important;
            border-bottom: 1px solid #263645 !important;
        }

        body.dark-mode .modal-title {
            color: #E5E8EB !important;
        }

        body.dark-mode .modal-body {
            color: #E5E8EB !important;
        }

        body.dark-mode .modal-footer {
            background-color: #121A21 !important;
            border-top: 1px solid #263645 !important;
        }

        /* Close button */
        body.dark-mode .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%) !important;
        }

        /* Scrollbar for dark mode */
        body.dark-mode ::-webkit-scrollbar-track {
            background: #121A21 !important;
        }

        body.dark-mode ::-webkit-scrollbar-thumb {
            background-color: #263645 !important;
            border: 3px solid #121A21 !important;
        }

        body.dark-mode ::-webkit-scrollbar-thumb:hover {
            background-color: #1C7DD6 !important;
        }

        /* Button group actions */
        body.dark-mode .btn-group-action .btn {
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }

        body.dark-mode .btn-group-action .btn:last-child {
            margin-right: 0;
        }

        /* Strong text */
        body.dark-mode strong {
            color: #E5E8EB !important;
        }

        /* Small text */
        body.dark-mode small {
            color: #94ADC7 !important;
        }

        /* Container backgrounds */
        body.dark-mode .container-fluid {
            background-color: #121A21 !important;
        }

        /* Focus states */
        body.dark-mode .btn:focus,
        body.dark-mode .form-control:focus,
        body.dark-mode .form-select:focus {
            box-shadow: 0 0 0 0.2rem rgba(28, 125, 214, 0.25) !important;
        }

        /* Links */
        body.dark-mode a {
            color: #1C7DD6 !important;
        }

        body.dark-mode a:hover {
            color: #94ADC7 !important;
        }

        /* Table responsive container */
        body.dark-mode .table-responsive {
            border-color: #121A21 !important;
        }

        /* SVG icons in buttons */
        body.dark-mode .btn-success i {
            color: #ffffff !important;
        }

        body.dark-mode .btn-danger i {
            color: #ffffff !important;
        }

        body.dark-mode .btn-info i {
            color: #000000 !important;
        }

        body.dark-mode .btn-primary i {
            color: #ffffff !important;
        }

        /* Modal submit button states */
        body.dark-mode #modalSubmitBtn.btn-success {
            background-color: #28a745 !important;
            border-color: #28a745 !important;
            color: #ffffff !important;
        }

        body.dark-mode #modalSubmitBtn.btn-danger {
            background-color: #dc3545 !important;
            border-color: #dc3545 !important;
            color: #ffffff !important;
        }

        body.dark-mode #modalSubmitBtn.btn-info {
            background-color: #0dcaf0 !important;
            border-color: #0dcaf0 !important;
            color: #000000 !important;
        }

        /* Readonly inputs */
        body.dark-mode .form-control[readonly] {
            background-color: #121A21 !important;
            color: #94ADC7 !important;
            border-color: #263645 !important;
        }

        /* Time inputs */
        body.dark-mode input[type="time"]::-webkit-calendar-picker-indicator {
            filter: invert(1) brightness(1.5);
        }

        /* Placeholder text */
        body.dark-mode ::placeholder {
            color: #94ADC7 !important;
        }

        /* Textarea specific */
        body.dark-mode textarea.form-control {
            min-height: 144px;
            resize: none;
            background-color: #121A21 !important;
            border: 1px solid #263645 !important;
            color: #E5E8EB !important;
        }

        /* Button sizes */
        body.dark-mode .btn-sm {
            height: 32px;
            padding: 4px 12px;
            font-size: 0.875rem;
        }

        /* Media query adjustments */
        @media (max-width: 768px) {
            body.dark-mode .layout-content-container {
                margin-left: 0 !important;
                padding: 1rem !important;
            }

            body.dark-mode .card {
                padding: 1rem !important;
            }

            body.dark-mode .btn-group-action .btn {
                width: 100% !important;
                margin-right: 0 !important;
                margin-bottom: 0.5rem !important;
            }

            body.dark-mode .table-responsive {
                margin: 0 -1rem !important;
                width: calc(100% + 2rem) !important;
            }
        }

        /* Form validation states */
        body.dark-mode .is-valid {
            border-color: #28a745 !important;
            background-color: rgba(40, 167, 69, 0.1) !important;
        }

        body.dark-mode .is-invalid {
            border-color: #dc3545 !important;
            background-color: rgba(220, 53, 69, 0.1) !important;
        }

        /* Dropdowns */
        body.dark-mode .dropdown-menu {
            background-color: #263645 !important;
            border: 1px solid #121A21 !important;
            color: #E5E8EB !important;
        }

        body.dark-mode .dropdown-item {
            color: #E5E8EB !important;
        }

        body.dark-mode .dropdown-item:hover {
            background-color: rgba(28, 125, 214, 0.2) !important;
            color: #FFFFFF !important;
        }

        /* Tooltips */
        body.dark-mode .tooltip-inner {
            background-color: #263645 !important;
            color: #E5E8EB !important;
        }

        body.dark-mode .tooltip-arrow::before {
            border-top-color: #263645 !important;
        }

        /* Form groups */
        body.dark-mode .mb-3 {
            margin-bottom: 1rem !important;
        }

        /* Modal form styling */
        body.dark-mode #proposedScheduleDisplay {
            color: #E5E8EB !important;
        }

        body.dark-mode #proposedScheduleDisplay strong {
            color: #94ADC7 !important;
        }

        /* H6 headings in modal */
        body.dark-mode h6 {
            color: #E5E8EB !important;
            margin-bottom: 1rem !important;
        }
    </style>
</head>

<body>
    <?php require_once '../../templates/admin/sidenav_admin.php'; ?>

    <div class="layout-container d-flex flex-column">
        <div class="container-fluid flex-grow-1 py-3">
            <div class="row justify-content-center">
                <div class="col-12">
                    <div class="layout-content-container px-4">
                        <!-- Page header -->
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 p-2">
                            <h1 class="page-title m-0 fs-3">Office Hours Requests</h1>
                        </div>

                        <?php if (!empty($message)): ?>
                            <div class="alert alert-<?= htmlspecialchars($message_type) ?> alert-dismissible fade show"
                                role="alert">
                                <?= htmlspecialchars($message) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Office Hours Requests Section -->
                        <div class="card p-3">
                            <h2 class="section-title px-3 py-3">Pending & Resolved Requests</h2>
                            <?php if (!empty($officeHoursRequests)): ?>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Faculty</th>
                                                <th>Proposed Schedule</th>
                                                <th>Message</th>
                                                <th>Status</th>
                                                <th>Admin Reply</th>
                                                <th>Requested At</th>
                                                <th>Responded At</th>
                                                <th>Approved Schedule</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($officeHoursRequests as $request): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($request['id']) ?></td>
                                                    <td><?= htmlspecialchars($request['faculty_name']) ?>
                                                        (<?= htmlspecialchars($request['faculty_email']) ?>)</td>
                                                    <td><?= htmlspecialchars($request['proposed_day']) ?>
                                                        <?= htmlspecialchars(date('h:i A', strtotime($request['proposed_start_time']))) ?>
                                                        -
                                                        <?= htmlspecialchars(date('h:i A', strtotime($request['proposed_end_time']))) ?>
                                                    </td>
                                                    <td><small><?= nl2br(htmlspecialchars($request['request_letter_message'])) ?></small>
                                                    </td>
                                                    <td>
                                                        <span
                                                            class="badge bg-<?= strtolower($request['status']) === 'approved' ? 'success' : (strtolower($request['status']) === 'pending' ? 'warning' : (strtolower($request['status']) === 'revised' ? 'info' : 'danger')) ?>">
                                                            <?= ucfirst(htmlspecialchars($request['status'])) ?>
                                                        </span>
                                                    </td>
                                                    <td><small><?= nl2br(htmlspecialchars($request['admin_reply_message'] ?: 'N/A')) ?></small>
                                                    </td>
                                                    <td><?= htmlspecialchars(date('M d, Y h:i A', strtotime($request['requested_at']))) ?>
                                                    </td>
                                                    <td><?= $request['responded_at'] ? htmlspecialchars(date('M d, Y h:i A', strtotime($request['responded_at']))) : 'N/A' ?>
                                                    </td>
                                                    <td><?= $request['approved_day'] ? htmlspecialchars($request['approved_day']) . ' ' . htmlspecialchars(date('h:i A', strtotime($request['approved_start_time']))) . ' - ' . htmlspecialchars(date('h:i A', strtotime($request['approved_end_time']))) : 'N/A' ?>
                                                    </td>
                                                    <td class="btn-group-action">
                                                        <?php if ($request['status'] === 'pending' || $request['status'] === 'revised'): ?>
                                                            <button class="btn btn-sm btn-success approve-btn"
                                                                data-bs-toggle="modal" data-bs-target="#approveRejectModal"
                                                                data-request-id="<?= htmlspecialchars($request['id']) ?>"
                                                                data-faculty-name="<?= htmlspecialchars($request['faculty_name']) ?>"
                                                                data-proposed-day="<?= htmlspecialchars($request['proposed_day']) ?>"
                                                                data-proposed-start="<?= htmlspecialchars($request['proposed_start_time']) ?>"
                                                                data-proposed-end="<?= htmlspecialchars($request['proposed_end_time']) ?>"
                                                                data-mode="approve">
                                                                <i class="fas fa-check-circle"></i> Approve
                                                            </button>
                                                            <button class="btn btn-sm btn-info revise-btn" data-bs-toggle="modal"
                                                                data-bs-target="#approveRejectModal"
                                                                data-request-id="<?= htmlspecialchars($request['id']) ?>"
                                                                data-faculty-name="<?= htmlspecialchars($request['faculty_name']) ?>"
                                                                data-proposed-day="<?= htmlspecialchars($request['proposed_day']) ?>"
                                                                data-proposed-start="<?= htmlspecialchars($request['proposed_start_time']) ?>"
                                                                data-proposed-end="<?= htmlspecialchars($request['proposed_end_time']) ?>"
                                                                data-mode="revise">
                                                                <i class="fas fa-redo-alt"></i> Revise
                                                            </button>
                                                            <button class="btn btn-sm btn-danger reject-btn" data-bs-toggle="modal"
                                                                data-bs-target="#approveRejectModal"
                                                                data-request-id="<?= htmlspecialchars($request['id']) ?>"
                                                                data-faculty-name="<?= htmlspecialchars($request['faculty_name']) ?>"
                                                                data-mode="reject">
                                                                <i class="fas fa-times-circle"></i> Reject
                                                            </button>
                                                        <?php else: ?>
                                                            <span class="text-muted">No actions</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="empty-state-text px-3 m-3">No office hour requests found.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Approve/Reject Modal -->
    <div class="modal fade" id="approveRejectModal" tabindex="-1" aria-labelledby="approveRejectModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="approveRejectModalLabel">Process Office Hour Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="../../backend/admin/office_hours_requests_logic.php" method="POST"
                        id="processRequestForm">
                        <input type="hidden" name="action" id="modalAction">
                        <input type="hidden" name="request_id" id="modalRequestId">

                        <p>Request from: <strong id="modalFacultyName"></strong></p>
                        <p id="proposedScheduleDisplay">Proposed Schedule: <strong id="modalProposedSchedule"></strong>
                        </p>

                        <div id="approvedRevisedFields" style="display:none;">
                            <h6>Approved/Revised Schedule:</h6>
                            <div class="mb-3">
                                <label for="approvedDay" class="form-label">Day(s) of Week:</label>
                                <input type="text" class="form-control" id="approvedDay" name="approved_day" required>
                            </div>
                            <div class="mb-3">
                                <label for="approvedStartTime" class="form-label">Start Time:</label>
                                <input type="time" class="form-control" id="approvedStartTime"
                                    name="approved_start_time" required>
                            </div>
                            <div class="mb-3">
                                <label for="approvedEndTime" class="form-label">End Time:</label>
                                <input type="time" class="form-control" id="approvedEndTime" name="approved_end_time"
                                    required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="adminReply" class="form-label">Your Reply/Message to Faculty:</label>
                            <textarea class="form-control" id="adminReply" name="admin_reply" rows="3"
                                required></textarea>
                        </div>

                        <div class="modal-footer justify-content-between px-0 pb-0 pt-3">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary" id="modalSubmitBtn">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <!-- JQuery Library -->
    <script src="../../assets/js/jquery.min.js"></script>

    <script>
        var approveRejectModal = document.getElementById('approveRejectModal');
        approveRejectModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var requestId = button.getAttribute('data-request-id');
            var facultyName = button.getAttribute('data-faculty-name');
            var mode = button.getAttribute('data-mode');

            var modalTitle = approveRejectModal.querySelector('.modal-title');
            var modalActionInput = approveRejectModal.querySelector('#modalAction');
            var modalRequestIdInput = approveRejectModal.querySelector('#modalRequestId');
            var modalFacultyName = approveRejectModal.querySelector('#modalFacultyName');
            var modalProposedSchedule = approveRejectModal.querySelector('#modalProposedSchedule');
            var adminReplyTextarea = approveRejectModal.querySelector('#adminReply');
            var approvedRevisedFields = approveRejectModal.querySelector('#approvedRevisedFields');
            var approvedDayInput = approveRejectModal.querySelector('#approvedDay');
            var approvedStartTimeInput = approveRejectModal.querySelector('#approvedStartTime');
            var approvedEndTimeInput = approveRejectModal.querySelector('#approvedEndTime');
            var modalSubmitBtn = approveRejectModal.querySelector('#modalSubmitBtn');

            modalRequestIdInput.value = requestId;
            modalFacultyName.textContent = facultyName;
            modalActionInput.value = mode + '_request';

            adminReplyTextarea.value = '';
            approvedRevisedFields.style.display = 'none';
            approvedDayInput.removeAttribute('required');
            approvedStartTimeInput.removeAttribute('required');
            approvedEndTimeInput.removeAttribute('required');

            if (mode === 'approve' || mode === 'revise') {
                var proposedDay = button.getAttribute('data-proposed-day');
                var proposedStart = button.getAttribute('data-proposed-start');
                var proposedEnd = button.getAttribute('data-proposed-end');

                function formatTime(timeStr) {
                    const [hours, minutes] = timeStr.split(':');
                    const date = new Date();
                    date.setHours(parseInt(hours), parseInt(minutes));
                    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: true });
                }

                modalProposedSchedule.textContent = `${proposedDay} ${formatTime(proposedStart)} - ${formatTime(proposedEnd)}`;
                approvedRevisedFields.style.display = 'block';
                approvedDayInput.setAttribute('required', 'required');
                approvedStartTimeInput.setAttribute('required', 'required');
                approvedEndTimeInput.setAttribute('required', 'required');

                approvedDayInput.value = proposedDay;
                approvedStartTimeInput.value = proposedStart;
                approvedEndTimeInput.value = proposedEnd;
            } else {
                modalProposedSchedule.textContent = "N/A";
            }

            if (mode === 'approve') {
                modalTitle.textContent = 'Approve Office Hour Request';
                adminReplyTextarea.placeholder = 'e.g., "Your office hours have been approved as requested."';
                adminReplyTextarea.value = 'Your office hours have been approved as requested.';
                modalSubmitBtn.textContent = 'Approve Request';
                modalSubmitBtn.className = 'btn btn-success';
            } else if (mode === 'reject') {
                modalTitle.textContent = 'Reject Office Hour Request';
                adminReplyTextarea.placeholder = 'e.g., "Your request has been rejected due to schedule conflict. Please resubmit."';
                adminReplyTextarea.value = '';
                modalSubmitBtn.textContent = 'Reject Request';
                modalSubmitBtn.className = 'btn btn-danger';
            } else if (mode === 'revise') {
                modalTitle.textContent = 'Revise Office Hour Request';
                adminReplyTextarea.placeholder = 'e.g., "We can approve these hours if you shift them by 30 mins earlier."';
                adminReplyTextarea.value = '';
                modalSubmitBtn.textContent = 'Send Revision';
                modalSubmitBtn.className = 'btn btn-info';
            }
        });
    </script>

    <?php require_once '../../templates/footer.php'; ?>
</body>

</html>

<script>
    document.body.style.backgroundColor = "#ffffff";
</script>