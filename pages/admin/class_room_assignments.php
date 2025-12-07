<?php
// CHRONONAV_WEB_DOSS/pages/admin/class_room_assignments.php

require_once '../../middleware/auth_check.php';
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';
require_once '../../backend/admin/class_room_assignments_logic.php';

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

$page_title = "Class Offerings & Assignments";
$current_page = "class_room_assignments";

$message = $_SESSION['message'] ?? '';
$message_type = $_SESSION['message_type'] ?? '';
unset($_SESSION['message']);
unset($_SESSION['message_type']);

$facultyUsers = getFacultyUsers($conn);
$allClassOfferings = getAllClassOfferings($conn);
$allRooms = getAllRooms($conn);

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

        .info-text {
            color: #0e151b;
        }

        .empty-state-text {
            color: #507495;
            font-size: 14px;
            background: #cff4fc;
            padding: 8px;
            border-radius: 4px;
        }

        .card {
            background-color: #ffffff;
            border: none;
            border-radius: 8px;
            padding: 2rem;
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




        /* ====================================================================== */
        /* Dark Mode Overrides for Class Room Assignments Page                    */
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
        body.dark-mode .form-select {
            background-color: #121A21 !important;
            border: 1px solid #263645 !important;
            color: #E5E8EB !important;
            height: 56px;
            padding: 15px;
        }

        body.dark-mode .form-control:focus,
        body.dark-mode .form-select:focus {
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
            border-left: 0 !important;
        }

        body.dark-mode .input-group .form-control {
            border-right: 0 !important;
        }

        /* Textarea */
        body.dark-mode textarea.form-control {
            background-color: #121A21 !important;
            border: 1px solid #263645 !important;
            color: #E5E8EB !important;
            min-height: 144px;
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

        body.dark-mode .btn-warning {
            background-color: #ffc107 !important;
            border-color: #ffc107 !important;
            color: #000000 !important;
        }

        body.dark-mode .btn-warning:hover {
            background-color: #ffca2c !important;
            border-color: #ffca2c !important;
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

        /* Text colors */
        body.dark-mode .info-text {
            color: #E5E8EB !important;
        }

        body.dark-mode .empty-state-text {
            color: #94ADC7 !important;
            background: rgba(28, 125, 214, 0.1) !important;
            padding: 8px;
            border-radius: 4px;
        }

        body.dark-mode .text-muted {
            color: #94ADC7 !important;
        }

        body.dark-mode small.text-danger {
            color: #e57373 !important;
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

        /* Select dropdown styling */
        body.dark-mode .form-select option {
            background-color: #263645 !important;
            color: #E5E8EB !important;
        }

        /* SVG icons */
        body.dark-mode svg {
            color: #94ADC7 !important;
        }

        body.dark-mode .btn-primary svg {
            color: #FFFFFF !important;
        }

        body.dark-mode .btn-warning svg {
            color: #000000 !important;
        }

        body.dark-mode .btn-danger svg {
            color: #FFFFFF !important;
        }

        /* Button groups */
        body.dark-mode .btn-group-action .btn {
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }

        /* Container backgrounds */
        body.dark-mode .container-fluid {
            background-color: #121A21 !important;
        }

        body.dark-mode .row {
            background-color: transparent !important;
        }

        body.dark-mode .col-12 {
            background-color: transparent !important;
        }

        /* Focus states */
        body.dark-mode .btn:focus {
            box-shadow: 0 0 0 0.2rem rgba(28, 125, 214, 0.25) !important;
        }

        /* Readonly inputs */
        body.dark-mode .form-control[readonly] {
            background-color: #121A21 !important;
            color: #94ADC7 !important;
            border-color: #263645 !important;
        }

        /* Disabled form elements */
        body.dark-mode .form-control:disabled,
        body.dark-mode .form-select:disabled {
            background-color: #263645 !important;
            color: #94ADC7 !important;
            border-color: #121A21 !important;
            opacity: 0.6;
        }

        /* Links in dark mode */
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

        /* Badges */
        body.dark-mode .badge {
            background-color: rgba(28, 125, 214, 0.2) !important;
            color: #94ADC7 !important;
            border: 1px solid #263645 !important;
        }

        /* Small text helpers */
        body.dark-mode small {
            color: #94ADC7 !important;
        }

        /* Grid spacing */
        body.dark-mode .row .col-12 {
            padding: 0.5rem;
        }

        /* Media query adjustments */
        @media (max-width: 767px) {
            body.dark-mode .layout-content-container {
                padding: 1rem !important;
            }

            body.dark-mode .card {
                padding: 1.5rem !important;
            }
        }

        /* Time input styling */
        body.dark-mode input[type="time"]::-webkit-calendar-picker-indicator {
            filter: invert(1) brightness(1.5);
        }

        /* Placeholder text for select */
        body.dark-mode option[value=""] {
            color: #94ADC7 !important;
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

        /* Dropdown styling */
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

        /* Button group spacing */
        body.dark-mode .btn-group-action {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        /* Input group focus state */
        body.dark-mode .input-group:focus-within .form-control,
        body.dark-mode .input-group:focus-within .input-group-text {
            border-color: #1C7DD6 !important;
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
                            <h1 class="page-title m-0 fs-3">Class Offerings & Assignments</h1>
                        </div>

                        <?php if (!empty($message)): ?>
                            <div class="alert alert-<?= htmlspecialchars($message_type) ?> alert-dismissible fade show"
                                role="alert">
                                <?= htmlspecialchars($message) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Add New Class Offering Section -->
                        <div class="card">
                            <h2 class="section-title px-3 pb-3 pt-0">Add New Class Offering</h2>
                            <form action="../../backend/admin/class_room_assignments_logic.php" method="POST">
                                <input type="hidden" name="action" value="add_class_offering">

                                <div class="row px-3 py-2">
                                    <div class="col-12 col-md-6 w-100">
                                        <div class="mb-3">
                                            <label class="form-label">Class Name</label>
                                            <input type="text" class="form-control" name="class_name" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="row px-3 py-2">
                                    <div class="col-12 col-md-6 w-100">
                                        <div class="mb-3">
                                            <label class="form-label">Class Code</label>
                                            <input type="text" class="form-control" name="class_code"
                                                placeholder="e.g., IT201, CS305" required>
                                            <small class="text-muted">This identifies the course itself. If you offer
                                                the same course multiple times, use the same code.</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="row px-3 py-2">
                                    <div class="col-12 col-md-6 w-100">
                                        <div class="mb-3">
                                            <label class="form-label">Semester</label>
                                            <input type="text" class="form-control" name="semester"
                                                placeholder="e.g., Fall 2025, Spring 2026" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="row px-3 py-2">
                                    <div class="col-12 col-md-6 w-100">
                                        <div class="mb-3">
                                            <label class="form-label">Assign Faculty</label>
                                            <select class="form-control" name="faculty_id" required>
                                                <option value="">-- Select Faculty --</option>
                                                <?php foreach ($facultyUsers as $faculty): ?>
                                                    <option value="<?= htmlspecialchars($faculty['id']) ?>">
                                                        <?= htmlspecialchars($faculty['name']) ?>
                                                        (<?= htmlspecialchars($faculty['email']) ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <?php if (empty($facultyUsers)): ?>
                                                <small class="text-danger">No faculty users found. Please add faculty users
                                                    in User Management.</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="row px-3 py-2">
                                    <div class="col-12 col-md-6 w-100">
                                        <div class="mb-3">
                                            <label class="form-label">Assign Room</label>
                                            <select class="form-control" name="room_id" required>
                                                <option value="">-- Select Room --</option>
                                                <?php foreach ($allRooms as $room): ?>
                                                    <option value="<?= htmlspecialchars($room['id']) ?>">
                                                        <?= htmlspecialchars($room['room_name']) ?> (Capacity:
                                                        <?= htmlspecialchars($room['capacity']) ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <?php if (empty($allRooms)): ?>
                                                <small class="text-danger">No rooms found. You might need a separate page to
                                                    add rooms first.</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="row px-3 py-2">
                                    <div class="col-12 col-md-6 w-100">
                                        <div class="mb-3">
                                            <label class="form-label">Day(s) of Week</label>
                                            <input type="text" class="form-control" name="day_of_week"
                                                placeholder="e.g., Monday, TTh, MWF" required>
                                            <small class="text-muted">Enter days like 'Monday', 'Tuesday', or 'MWF',
                                                'TTh' for multiple days. Be consistent.</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="row px-3 py-2">
                                    <div class="col-12 col-md-6 w-50">
                                        <div class="mb-3">
                                            <label class="form-label">Start Time</label>
                                            <div class="input-group">
                                                <input type="time" class="form-control" name="start_time" required>
                                                <span class="input-group-text">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px"
                                                        fill="currentColor" viewBox="0 0 256 256">
                                                        <path
                                                            d="M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm0,192a88,88,0,1,1,88-88A88.1,88.1,0,0,1,128,216Zm64-88a8,8,0,0,1-8,8H128a8,8,0,0,1-8-8V72a8,8,0,0,1,16,0v48h48A8,8,0,0,1,192,128Z">
                                                        </path>
                                                    </svg>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row px-3 py-2">
                                    <div class="col-12 col-md-6 w-50">
                                        <div class="mb-3">
                                            <label class="form-label">End Time</label>
                                            <div class="input-group">
                                                <input type="time" class="form-control" name="end_time" required>
                                                <span class="input-group-text">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px"
                                                        fill="currentColor" viewBox="0 0 256 256">
                                                        <path
                                                            d="M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm0,192a88,88,0,1,1,88-88A88.1,88.1,0,0,1,128,216Zm64-88a8,8,0,0,1-8,8H128a8,8,0,0,1-8-8V72a8,8,0,0,1,16,0v48h48A8,8,0,0,1,192,128Z">
                                                        </path>
                                                    </svg>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="px-3 py-2">
                                    <button type="submit" class="btn btn-primary d-flex align-items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20px" height="20px"
                                            fill="currentColor" viewBox="0 0 256 256">
                                            <path
                                                d="M224,128a8,8,0,0,1-8,8H136v80a8,8,0,0,1-16,0V136H40a8,8,0,0,1,0-16h80V40a8,8,0,0,1,16,0v80h80A8,8,0,0,1,224,128Z">
                                            </path>
                                        </svg>
                                        Add Class Offering
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Current Class Offerings Section -->
                        <div class="card">
                            <h2 class="section-title px-3 pb-3 pt-0">Current Class Offerings</h2>
                            <?php if (!empty($allClassOfferings)): ?>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Class Code</th>
                                                <th>Class Name</th>
                                                <th>Semester</th>
                                                <th>Faculty</th>
                                                <th>Room</th>
                                                <th>Schedule</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($allClassOfferings as $offering): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($offering['class_id']) ?></td>
                                                    <td><?= htmlspecialchars($offering['class_code']) ?></td>
                                                    <td><?= htmlspecialchars($offering['class_name']) ?></td>
                                                    <td><?= htmlspecialchars($offering['semester']) ?></td>
                                                    <td><?= htmlspecialchars($offering['faculty_name'] ?? 'N/A') ?></td>
                                                    <td><?= htmlspecialchars($offering['room_name'] ?? 'N/A') ?></td>
                                                    <td><?= htmlspecialchars($offering['day_of_week']) ?>
                                                        <?= htmlspecialchars(date('h:i A', strtotime($offering['start_time']))) ?>
                                                        -
                                                        <?= htmlspecialchars(date('h:i A', strtotime($offering['end_time']))) ?>
                                                    </td>
                                                    <td class="btn-group-action">
                                                        <button class="btn btn-sm btn-warning edit-offering-btn"
                                                            data-bs-toggle="modal" data-bs-target="#editClassOfferingModal"
                                                            data-id="<?= htmlspecialchars($offering['class_id']) ?>"
                                                            data-class-name="<?= htmlspecialchars($offering['class_name']) ?>"
                                                            data-class-code="<?= htmlspecialchars($offering['class_code']) ?>"
                                                            data-semester="<?= htmlspecialchars($offering['semester']) ?>"
                                                            data-faculty-id="<?= htmlspecialchars($offering['faculty_id']) ?>"
                                                            data-room-id="<?= htmlspecialchars($offering['room_id']) ?>"
                                                            data-day="<?= htmlspecialchars($offering['day_of_week']) ?>"
                                                            data-start-time="<?= htmlspecialchars($offering['start_time']) ?>"
                                                            data-end-time="<?= htmlspecialchars($offering['end_time']) ?>">
                                                            <i class="fas fa-edit"></i> Edit
                                                        </button>
                                                        <form action="../../backend/admin/class_room_assignments_logic.php"
                                                            method="POST" style="display:inline;"
                                                            onsubmit="return confirm('Are you sure you want to delete this class offering? This cannot be undone.');">
                                                            <input type="hidden" name="action" value="delete_class_offering">
                                                            <input type="hidden" name="class_id"
                                                                value="<?= htmlspecialchars($offering['class_id']) ?>">
                                                            <button type="submit" class="btn btn-sm btn-danger">
                                                                <i class="fas fa-trash-alt"></i> Delete
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="empty-state-text px-3">No class offerings found.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Class Offering Modal -->
    <div class="modal fade" id="editClassOfferingModal" tabindex="-1" aria-labelledby="editClassOfferingModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editClassOfferingModalLabel">Edit Class Offering</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="../../backend/admin/class_room_assignments_logic.php" method="POST">
                        <input type="hidden" name="action" value="edit_class_offering">
                        <input type="hidden" id="editClassOfferingId" name="class_id">

                        <div class="mb-3">
                            <label for="editClassName" class="form-label">Class Name:</label>
                            <input type="text" class="form-control" id="editClassName" name="class_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="editClassCode" class="form-label">Class Code:</label>
                            <input type="text" class="form-control" id="editClassCode" name="class_code" required>
                        </div>
                        <div class="mb-3">
                            <label for="editSemester" class="form-label">Semester:</label>
                            <input type="text" class="form-control" id="editSemester" name="semester" required>
                        </div>

                        <div class="mb-3">
                            <label for="editFacultyId" class="form-label">Assign Faculty:</label>
                            <select class="form-control" id="editFacultyId" name="faculty_id" required>
                                <option value="">-- Select Faculty --</option>
                                <?php foreach ($facultyUsers as $faculty): ?>
                                    <option value="<?= htmlspecialchars($faculty['id']) ?>">
                                        <?= htmlspecialchars($faculty['name']) ?>
                                        (<?= htmlspecialchars($faculty['email']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="editRoomId" class="form-label">Assign Room:</label>
                            <select class="form-control" id="editRoomId" name="room_id" required>
                                <option value="">-- Select Room --</option>
                                <?php foreach ($allRooms as $room): ?>
                                    <option value="<?= htmlspecialchars($room['id']) ?>">
                                        <?= htmlspecialchars($room['room_name']) ?> (Capacity:
                                        <?= htmlspecialchars($room['capacity']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="editDayOfWeek" class="form-label">Day(s) of Week:</label>
                            <input type="text" class="form-control" id="editDayOfWeek" name="day_of_week"
                                placeholder="e.g., Monday, TTh, MWF" required>
                        </div>
                        <div class="mb-3">
                            <label for="editStartTime" class="form-label">Start Time:</label>
                            <input type="time" class="form-control" id="editStartTime" name="start_time" required>
                        </div>
                        <div class="mb-3">
                            <label for="editEndTime" class="form-label">End Time:</label>
                            <input type="time" class="form-control" id="editEndTime" name="end_time" required>
                        </div>

                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- JQuery Library -->
    <script src="../../assets/js/jquery.min.js"></script>

    <script>
        var editClassOfferingModal = document.getElementById('editClassOfferingModal');
        editClassOfferingModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;

            var id = button.getAttribute('data-id');
            var className = button.getAttribute('data-class-name');
            var classCode = button.getAttribute('data-class-code');
            var semester = button.getAttribute('data-semester');
            var facultyId = button.getAttribute('data-faculty-id');
            var roomId = button.getAttribute('data-room-id');
            var day = button.getAttribute('data-day');
            var startTime = button.getAttribute('data-start-time');
            var endTime = button.getAttribute('data-end-time');

            var modalClassOfferingIdInput = editClassOfferingModal.querySelector('#editClassOfferingId');
            var modalClassNameInput = editClassOfferingModal.querySelector('#editClassName');
            var modalClassCodeInput = editClassOfferingModal.querySelector('#editClassCode');
            var modalSemesterInput = editClassOfferingModal.querySelector('#editSemester');
            var modalFacultySelect = editClassOfferingModal.querySelector('#editFacultyId');
            var modalRoomSelect = editClassOfferingModal.querySelector('#editRoomId');
            var modalDayInput = editClassOfferingModal.querySelector('#editDayOfWeek');
            var modalStartTimeInput = editClassOfferingModal.querySelector('#editStartTime');
            var modalEndTimeInput = editClassOfferingModal.querySelector('#editEndTime');

            modalClassOfferingIdInput.value = id;
            modalClassNameInput.value = className;
            modalClassCodeInput.value = classCode;
            modalSemesterInput.value = semester;
            modalFacultySelect.value = facultyId;
            modalRoomSelect.value = roomId || '';
            modalDayInput.value = day;
            modalStartTimeInput.value = startTime;
            modalEndTimeInput.value = endTime;
        });
    </script>

    <?php require_once '../../templates/footer.php'; ?>
</body>

</html>


<script>
    document.body.style.backgroundColor = "#ffffff";
</script>