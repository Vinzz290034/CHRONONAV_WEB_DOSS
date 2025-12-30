<?php


// CHRONONAV_WEB_DOSS/pages/user/dashboard.php

require_once '../../middleware/auth_check.php';
require_once '../../config/db_connect.php'; // Using mysqli connection (assumed)
require_once '../../includes/onboarding_functions.php';
require_once '../../includes/onboarding_module.php';

$user = $_SESSION['user'];
$page_title = "User Dashboard";
$current_page = "dashboard";
$display_name = htmlspecialchars($user['name'] ?? 'User');
$user_role = htmlspecialchars($user['role'] ?? 'user');
$user_id = $user['id'] ?? 0; // Get user ID for fetching schedule

// Assuming get_db_connection returns a valid mysqli connection ($conn)
// Reusing global $conn for schedule fetching if db_connect uses mysqli
global $conn; 

$onboarding_steps = [];
$pdo = null; // Initialize PDO as null for local scope

// Try to use mysqli connection first, as most files use it
if (isset($conn) && $conn instanceof mysqli) {
    try {
        // Attempt to convert mysqli connection to PDO for existing functions if needed,
        // but let's rewrite getUserSchedule to use mysqli directly for consistency.
        $pdo = null; // Ignore PDO setup for simplicity, use mysqli below
    } catch (Exception $e) {
        // Handle potential PDO connection error if functions require it
    }
}


// --- MODIFIED PHP FUNCTION: Fetch User's Schedule from add_pdf (MySQLi) ---
function getUserSchedule($user_id, $conn)
{
    if (!$conn || $conn->connect_error) {
        error_log("DB connection failed in getUserSchedule.");
        return [];
    }

    try {
        // Query the add_pdf table (schedule data)
        $stmt = $conn->prepare("
            SELECT title, start_time, end_time, day_of_week, room, schedule_code 
            FROM add_pdf 
            WHERE user_id = ? AND is_active = 1
            ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), start_time
        ");
        
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        } else {
            error_log("Failed to prepare user schedule statement: " . $conn->error);
            return [];
        }
    } catch (Exception $e) {
        error_log("Failed to fetch user schedule: " . $e->getMessage());
        return [];
    }
}
$user_schedule = getUserSchedule($user_id, $conn);
// --- END MODIFIED PHP FUNCTION ---

// Placeholder for onboarding steps (using empty array to avoid fatal error if onboarding functions are not available)
$onboarding_steps = [];


$header_path = '../../templates/user/header_user.php';
if (isset($user['role'])) {
    if ($user['role'] === 'admin') {
        $header_path = '../../templates/admin/header_admin.php';
    } elseif ($user['role'] === 'faculty') {
        $header_path = '../../templates/faculty/header_faculty.php';
    }
}
require_once $header_path;

?>

<link rel="stylesheet" href="../../assets/css/user_css/dashboards.css">

<style>
    /* CSS styles (unchanged) */
    body {
        background-color: rgb(255, 255, 255)
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

    /* Custom styles to match the exact design */
    .search-bar {
        display: flex;
        align-items: center;
        background-color: rgb(231, 231, 231);
        border-radius: 0.75rem;
        padding: 0px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
        margin-top: 15px;
        margin-bottom: 25px;
    }

    .search-bar-custom {
        height: 48px;
        background-color: #eaedf1;
        border-radius: 0.75rem;
        border: none;
    }

    .search-bar-custom .input-group-text {
        background-color: #eaedf1;
        border: none;
        border-radius: 0.75rem 0 0 0.75rem;
        color: #5c748a;
        padding-left: 1rem;
    }

    .search-bar-custom .form-control {
        background-color: #eaedf1;
        border: none;
        border-radius: 0 0.75rem 0.75rem 0;
        color: #101518;
        padding-left: 0.5rem;
        height: 48px;
    }

    .search-bar-custom .form-control:focus {
        box-shadow: none;
        background-color: #eaedf1;
    }

    .search-bar-custom .form-control::placeholder {
        color: #5c748a;
    }

    .study-load-card-custom {
        border-radius: 0.75rem;
        overflow: hidden;
        border: none;
        background: white;
    }

    .study-load-image-custom {
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        border-radius: 0.75rem;
        min-height: 200px;
        height: 100%;
    }

    .class-item-custom {
        background-color: #f9fafb;
        padding: 12px 16px;
        border-radius: 0.5rem;
        margin-bottom: 8px;
    }

    .class-image-custom {
        width: 56px;
        height: 74.67px;
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        border-radius: 0.5rem;
        flex-shrink: 0;
    }

    .btn-custom-outline {
        background-color: #eaedf1;
        color: #101518;
        border: none;
        border-radius: 9999px;
        font-weight: 500;
        font-size: 0.875rem;
        padding: 8px 16px;
        min-width: 84px;
        height: 32px;
    }

    .btn-custom-primary {
        background-color: #dce8f3;
        color: #101518;
        border: none;
        border-radius: 9999px;
        font-weight: 500;
        font-size: 0.875rem;
        padding: 8px 16px;
        min-width: 84px;
        height: 32px;
    }

    .text-truncate-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .welcome-title {
        font-size: 28px;
        font-weight: bold;
        color: #101518;
        line-height: 1.2;
    }

    .section-title {
        font-size: 22px;
        font-weight: bold;
        color: #101518;
        line-height: 1.2;
    }

    .onboarding-controls .btn-custom-blue,
    .onboarding-controls .btn-custom-primary,
    .onboarding-controls .btn-custom-outline {
        background: #E8EDF2;
    }

    .onboarding-controls .btn-custom-blue:hover,
    .onboarding-controls .btn-custom-primary:hover,
    .onboarding-controls .btn-custom-outline:hover {
        color: #2e78c6;
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



    /* Mobile: 767px and below */
    @media (max-width: 767px) {
        .main-dashboard-content {
            margin-left: 0;
            padding: 15px;
        }

        .welcome-title {
            font-size: 22px;
        }

        .section-title {
            font-size: 18px;
        }

        .search-bar-custom {
            height: 42px;
        }

        .search-bar-custom .form-control {
            height: 42px;
            font-size: 0.875rem;
        }

        input#searchInput {
            padding-right: 1rem;
        }

        .study-load-card-custom .row {
            flex-direction: column;
        }

        .study-load-image-custom {
            min-height: 150px;
        }

        .class-item-custom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
            padding: 12px 16px;
            gap: 10px;
            min-width: 100%;
        }

        .class-image-custom {
            width: 56px;
            height: 74.67px;
            flex-shrink: 0;
        }

        .class-item-custom .d-flex.flex-column.flex-grow-1 {
            min-width: 60%;
            gap: 10px;
        }

        .d-flex.align-items-center.flex-grow-1 {
            min-width: 60%;
        }

        .class-item-custom button {
            flex-shrink: 0;
            min-width: 70px;
        }

        .onboarding-controls .d-flex {
            flex-direction: column;
            gap: 10px;
        }

        .onboarding-controls .btn {
            width: 100%;
        }

        .dashboard-widgets-grid {
            margin-bottom: 20px;
        }
    }

    /* Tablet: 768px to 1023px */
    @media (min-width: 768px) and (max-width: 1023px) {
        .main-dashboard-content {
            margin-left: 80px;
            padding: 20px 25px;
        }

        .welcome-title {
            font-size: 24px;
        }

        .section-title {
            font-size: 20px;
        }

        .study-load-card-custom .row {
            flex-direction: row;
        }

        .study-load-image-custom {
            min-height: 180px;
        }

        .class-item-custom {
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
        }

        .class-image-custom {
            width: 56px;
            height: 74.67px;
        }
    }

    /* Desktop: 1024px and above */
    @media (min-width: 1024px) {
        .main-dashboard-content {
            margin-left: 20%;
            padding: 20px 35px;
        }

        .welcome-title {
            font-size: 28px;
        }

        .section-title {
            font-size: 22px;
        }

        .study-load-card-custom .row {
            flex-direction: row;
        }

        .study-load-image-custom {
            min-height: 200px;
        }

        .class-item-custom {
            flex-direction: row;
            align-items: center;
        }

        .class-image-custom {
            width: 56px;
            height: 74.67px;
        }
    }

    /* Demo content styles */
    body {
        font-family: 'Space Grotesk', 'Noto Sans', sans-serif;
        background-color: #f8f9fa;
        margin: 0;
        padding: 0;
    }

    .demo-content {
        background: white;
        border-radius: 8px;
        padding: 2rem;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .screen-size-indicator {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: #333;
        color: white;
        padding: 10px 15px;
        border-radius: 5px;
        font-size: 0.9rem;
    }


    /* ====================================================================== */
    /* Dark Mode Overrides for Dashboard - Custom Colors                      */
    /* ====================================================================== */
    body.dark-mode {
        background-color: #121A21 !important;
        color: #E5E8EB !important;
    }

    body.dark-mode .main-dashboard-content-wrapper {
        background-color: #121A21 !important;
        color: #E5E8EB !important;
    }

    body.dark-mode .main-dashboard-content {
        background-color: #121A21 !important;
        color: #E5E8EB !important;
    }

    body.dark-mode .welcome-title {
        color: #E5E8EB !important;
    }

    body.dark-mode .section-title {
        color: #E5E8EB !important;
    }

    body.dark-mode .search-bar-custom {
        background-color: #263645 !important;
        border: 1px solid #121A21 !important;
    }

    body.dark-mode .search-bar-custom .input-group-text {
        background-color: #263645 !important;
        color: #94ADC7 !important;
        border: none !important;
    }

    body.dark-mode .search-bar-custom .form-control {
        background-color: #263645 !important;
        color: #E5E8EB !important;
        border: none !important;
    }

    body.dark-mode .search-bar-custom .form-control::placeholder {
        color: #94ADC7 !important;
    }

    body.dark-mode .search-bar-custom .form-control:focus {
        background-color: #263645 !important;
        color: #E5E8EB !important;
        box-shadow: none !important;
    }

    body.dark-mode .card {
        background-color: #263645 !important;
        border: 1px solid #121A21 !important;
        color: #E5E8EB !important;
    }

    body.dark-mode .card.border-0 {
        background-color: #263645 !important;
        border: none !important;
    }

    body.dark-mode .onboarding-controls {
        background-color: #121A21 !important;
        border: 1px solid #263645 !important;
    }

    body.dark-mode .onboarding-controls h5 {
        color: #E5E8EB !important;
    }

    body.dark-mode .onboarding-controls p.text-muted {
        color: #94ADC7 !important;
    }

    body.dark-mode .btn-custom-outline {
        background-color: #121A21 !important;
        color: #94ADC7 !important;
        border: 1px solid #263645 !important;
    }

    body.dark-mode .btn-custom-outline:hover {
        background-color: #1C7DD6 !important;
        color: #FFFFFF !important;
        border-color: #1C7DD6 !important;
    }

    body.dark-mode .btn-custom-primary {
        background-color: #263645 !important;
        color: #94ADC7 !important;
        border: 1px solid #121A21 !important;
    }

    body.dark-mode .btn-custom-primary:hover {
        background-color: #1C7DD6 !important;
        color: #FFFFFF !important;
        border-color: #1C7DD6 !important;
    }

    body.dark-mode .btn-custom-blue {
        background-color: #121A21 !important;
        color: #94ADC7 !important;
        border: 1px solid #263645 !important;
    }

    body.dark-mode .btn-custom-blue:hover {
        background-color: #1C7DD6 !important;
        color: #FFFFFF !important;
        border-color: #1C7DD6 !important;
    }

    body.dark-mode .onboarding-controls .btn-custom-blue,
    body.dark-mode .onboarding-controls .btn-custom-primary,
    body.dark-mode .onboarding-controls .btn-custom-outline {
        background: #121A21 !important;
        color: #94ADC7 !important;
        border: 1px solid #263645 !important;
    }

    body.dark-mode .onboarding-controls .btn-custom-blue:hover,
    body.dark-mode .onboarding-controls .btn-custom-primary:hover,
    body.dark-mode .onboarding-controls .btn-custom-outline:hover {
        background-color: #1C7DD6 !important;
        color: #FFFFFF !important;
        border-color: #1C7DD6 !important;
    }

    body.dark-mode .study-load-card-custom {
        background: #263645 !important;
        border: 1px solid #121A21 !important;
    }

    body.dark-mode .study-load-card-custom .card-title {
        color: #E5E8EB !important;
    }

    body.dark-mode .study-load-card-custom p.text-muted {
        color: #94ADC7 !important;
    }

    body.dark-mode .class-item-custom {
        background-color: #121A21 !important;
        border: 1px solid #263645 !important;
    }

    body.dark-mode .class-item-custom:hover {
        background-color: rgba(28, 125, 214, 0.1) !important;
    }

    body.dark-mode .class-item-custom p.text-dark {
        color: #E5E8EB !important;
    }

    body.dark-mode .class-item-custom p.text-muted {
        color: #94ADC7 !important;
    }

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

    body.dark-mode .modal-body {
        color: #E5E8EB !important;
    }

    body.dark-mode .form-control {
        background-color: #121A21 !important;
        border: 1px solid #263645 !important;
        color: #E5E8EB !important;
    }

    body.dark-mode .form-control:focus {
        background-color: #121A21 !important;
        border-color: #1C7DD6 !important;
        color: #E5E8EB !important;
        box-shadow: 0 0 0 0.2rem rgba(28, 125, 214, 0.25) !important;
    }

    body.dark-mode .table {
        color: #E5E8EB !important;
    }

    body.dark-mode .table-striped tbody tr:nth-of-type(odd) {
        background-color: #121A21 !important;
    }

    body.dark-mode .table-striped tbody tr:nth-of-type(even) {
        background-color: #263645 !important;
    }

    body.dark-mode .table-hover tbody tr:hover {
        background-color: rgba(28, 125, 214, 0.2) !important;
    }

    body.dark-mode thead {
        background-color: #121A21 !important;
    }

    body.dark-mode .alert {
        background-color: #263645 !important;
        border: 1px solid #121A21 !important;
    }

    body.dark-mode .alert-info {
        background-color: #0D47A1 !important;
        color: #BBDEFB !important;
        border-color: #1565C0 !important;
    }

    body.dark-mode .alert-success {
        background-color: #1B5E20 !important;
        color: #C8E6C9 !important;
        border-color: #2E7D32 !important;
    }

    body.dark-mode .alert-danger {
        background-color: #B71C1C !important;
        color: #FFCDD2 !important;
        border-color: #C62828 !important;
    }

    body.dark-mode .text-dark {
        color: #E5E8EB !important;
    }

    body.dark-mode .text-muted {
        color: #94ADC7 !important;
    }

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

    body.dark-mode .demo-content {
        background-color: #263645 !important;
        color: #E5E8EB !important;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
    }

    body.dark-mode .screen-size-indicator {
        background: #1C7DD6 !important;
        color: #FFFFFF !important;
    }

    body.dark-mode .btn-close {
        filter: invert(1) grayscale(100%) brightness(200%) !important;
    }

    body.dark-mode .input-group-text {
        background-color: #121A21 !important;
        border: 1px solid #263645 !important;
        color: #94ADC7 !important;
    }

    @media (max-width: 767px) {
        body.dark-mode .main-dashboard-content {
            background-color: #121A21 !important;
        }

        body.dark-mode .class-item-custom {
            background-color: #121A21 !important;
        }
    }

    @media (min-width: 768px) and (max-width: 1023px) {
        body.dark-mode .main-dashboard-content {
            background-color: #121A21 !important;
        }
    }

    @media (min-width: 1024px) {
        body.dark-mode {
            background: #121A21 !important;
            background-color: #121A21 !important;
        }

        body.dark-mode .main-dashboard-content {
            background-color: #121A21 !important;
        }
    }

    body.dark-mode #searchResults {
        background-color: #263645 !important;
        border: 1px solid #121A21 !important;
    }

    body.dark-mode .list-group-item {
        background-color: #263645 !important;
        color: #E5E8EB !important;
        border-color: #121A21 !important;
    }

    body.dark-mode .list-group-item:hover {
        background-color: #1C7DD6 !important;
        color: #FFFFFF !important;
    }

    body.dark-mode .list-group-item.text-muted {
        color: #94ADC7 !important;
    }
</style>

<link rel="icon" type="image/x-icon"
    href="https://res.cloudinary.com/deua2yipj/image/upload/v1758917007/ChronoNav_logo_muon27.png">

<?php include('../../includes/semantics/head.php'); ?>

<div class="d-flex" id="wrapper" data-user-role="<?= $user_role ?>">
    <?php
    $sidenav_path = '../../templates/user/sidenav_user.php';
    if (isset($user['role'])) {
        if ($user['role'] === 'admin') {
            $sidenav_path = '../../templates/admin/sidenav_admin.php';
        } elseif ($user['role'] === 'faculty') {
            $sidenav_path = '../../templates/faculty/sidenav_faculty.php';
        }
    }
    require_once $sidenav_path;
    ?>

    <div class="main-dashboard-content-wrapper" id="page-content-wrapper">
        <div class="main-dashboard-content">
            <div class="d-flex flex-column px-3 pt-4 pb-2">
                <h2 class="welcome-title mb-0">Welcome, <?= htmlspecialchars($user['name']) ?></h2>
            </div>


            <div class="search-bar position-relative mb-4">
                <div class="input-group search-bar-custom">
                    <span class="input-group-text">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" id="searchInput" class="form-control"
                        placeholder="Search your schedule, courses, or reminders...">
                </div>
                <div id="searchResults" class="list-group position-absolute w-100 mt-1"
                    style="z-index:1000; display: none;"></div>

            </div>

            <div class="card p-4 mb-4 border-0">
                <p class="text-dark mb-3">This is your personal space in ChronoNav. Keep an eye on your upcoming
                    schedules and reminders.</p>
                <div class="onboarding-controls mt-4 p-3 border rounded">
                    <h5 class="text-dark fw-bold mb-3">Onboarding & Quick Guides</h5>
                    <p class="text-muted mb-3">Learn more about using ChronoNav, view helpful tips, or restart your
                        guided tour.</p>
                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-custom-blue rounded-pill" id="viewTourBtn">
                            <i class="fas fa-route me-1"></i> View Tour
                        </button>
                        <button class="btn btn-custom-primary rounded-pill" id="viewTipsBtn">
                            <i class="fas fa-lightbulb me-1"></i> View Tips
                        </button>
                        <button class="btn btn-custom-outline rounded-pill" id="restartOnboardingBtn">
                            <i class="fas fa-sync-alt me-1"></i> Restart Onboarding
                        </button>
                    </div>
                </div>
                <div id="onboardingContent" class="mt-3"></div>
            </div>

            <div class="dashboard-widgets-grid mb-4 px-3">
                <div class="card study-load-card-custom p-0 w-100">
                    <div class="row g-0">
                        <div class="col-12 col-xl-8">
                            <div class="study-load-image-custom"
                                style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuD954QIt-gjVs85lBbDP2JqyWtLS1JkL27Y6Or63Qm3SYXXEDGjS_1Zgz278DlR8dGPMDnq8uXRg0NvWFNn3z_sMSFPbzFtTechOl28InzjkBN3fVGWU6VTWRVXNPZ075PPeYJEwmqq6Ye_2n0zcXbwnnIIuRWWKPhUIkz9xa5GmLFWyH_h59WBby1QwlzB_LSr5EVKikvNrmrRTfzrOlefxoZ9Z6fADSNh2E7pi7YyQKNhdB6PlANQDzni9dCVi1p3Ucbkn9SEvw0");'>
                            </div>
                        </div>
                        <div class="col-12 col-xl-4">
                            <div class="card-body d-flex flex-column justify-content-center h-100 p-4">
                                <h5 class="card-title fw-bold mb-2">Add Study Load</h5>
                                <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-end">
                                    <div class="mb-3 mb-xl-0 flex-grow-1">
                                        <p class="text-muted mb-1">Get started by adding your courses for the semester.
                                        </p>
                                        <p class="text-muted mb-0">Plan your academic journey with ease. Add your
                                            courses and stay organized.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="px-3 pt-3 pb-1">
                <h3 class="section-title mb-0">Upcoming Classes</h3>
            </div>

            <div class="px-3">
                <?php if (!empty($user_schedule)): ?>
                    <?php foreach ($user_schedule as $class): ?>
                        <div class="class-item-custom d-flex align-items-center justify-content-between mb-2">
                            <div class="d-flex align-items-center flex-grow-1">
                                <div class="class-image-custom me-3"
                                    style='background-image: url("http://googleusercontent.com/profile/picture/<?= htmlspecialchars($class['schedule_code'] ?? 0) ?>");'>
                                </div>
                                <div class="d-flex flex-column flex-grow-1">
                                    <p class="text-dark fw-medium mb-1 text-truncate">
                                        <?= htmlspecialchars($class['title'] ?? 'N/A') ?>
                                    </p>
                                    <p class="text-muted small mb-0 text-truncate-2">
                                        <?= htmlspecialchars($class['room'] ?? 'N/A') ?> &middot; 
                                        <?= htmlspecialchars($class['start_time'] ?? 'N/A') ?> - 
                                        <?= htmlspecialchars($class['end_time'] ?? 'N/A') ?> (<?= htmlspecialchars($class['day_of_week'] ?? 'N/A') ?>)
                                    </p>
                                </div>
                            </div>
                            <button
                                class="btn btn-custom-outline ms-3 flex-shrink-0 btn-outline-secondary ms-3 flex-shrink-0 rounded-pill">
                                <span>delete</span>
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-info mt-3" role="alert">
                        No upcoming classes found. Please add your study load using the card above.
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<?php require_once '../../templates/common/onboarding_modal.php'; ?>

<script id="tour-data" type="application/json">
    <?= json_encode($onboarding_steps); ?>
</script>


<div class="modal fade" id="ocrModal" tabindex="-1" aria-labelledby="ocrModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">

                <h5 class="modal-title fw-bold" id="ocrModalLabel">OCR Study Load Reader</h5>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="ocr-alert" class="alert d-none" role="alert"></div>
                <div id="upload-step">

                    <h6 class="fw-medium">Step 1: Upload your PDF file</h6>

                    <div class="input-group mb-3">
                        <input type="file" class="form-control" id="studyLoadPdf" accept="application/pdf">
                        <label class="input-group-text" for="studyLoadPdf">Upload</label>
                    </div>

                    <button class="btn btn-primary" id="processOcrBtn">Process Document</button>
                </div>

                <div id="preview-step" style="display: none;">
                    <h6 class="fw-medium">Step 2: Preview Extracted Schedule</h6>
                    <div id="preview-content" class="p-3 border rounded mb-3"
                        style="max-height: 400px; overflow-y: auto;">
                        <p class="text-center text-muted">Awaiting file upload...</p>
                    </div>

                    <button class="btn btn-secondary me-2" id="backToUploadBtn">Back</button>
                    <button class="btn btn-success" id="confirmScheduleBtn">Confirm Extracted Schedule</button>
                </div>

                <div id="confirmation-step" style="display: none;">

                    <h6 class="fw-medium">Step 3: Confirmation</h6>

                    <p>Your study load has been successfully saved!</p>
                    <button class="btn btn-success" data-bs-dismiss="modal">Done</button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>
<script src="../../assets/js/jquery.min.js"></script>
<script src="../../assets/js/script.js"></script>
<script src="../../assets/js/onboarding_tour.js"></script>

<script>

    // ================== AJAX SEARCH ==================
    $("#searchInput").on("keyup", function () {
        let query = $(this).val();
        if (query.length > 2) {
            $.ajax({
                url: "../../pages/user/search.php",
                method: "GET",
                data: { q: query },
                success: function (response) {
                    let data = JSON.parse(response);
                    let output = "";
                    if (data.length > 0) {
                        data.forEach(item => {
                            output += `<a href="#" class="list-group-item list-group-item-action">${item.title}</a>`;
                        });
                    } else {
                        output = `<div class="list-group-item text-muted">No results found</div>`;
                    }
                    $("#searchResults").html(output).show();
                }
            });
        } else {
            $("#searchResults").hide();
        }
    });

    // ================== AJAX OCR UPLOAD ==================
    $("#processOcrBtn").click(function () {
        let file = $("#studyLoadPdf")[0].files[0];
        if (!file) {
            $("#ocr-alert").removeClass("d-none alert-success").addClass("alert-danger").text("Please upload a file first.");
            return;
        }

        let formData = new FormData();
        formData.append("studyLoadPdf", file);

        $.ajax({
            url: "../../pages/user/process_ocr.php",
            method: "POST",
            data: formData,
            processData: false,
            contentType: false,
            dataType: "json",
            success: function (response) {
                if (response.success) {
                    $("#upload-step").hide();
                    $("#preview-step").show();
                    $("#ocr-alert").removeClass("d-none alert-danger").addClass("alert-success").text("Document processed successfully! Please review the extracted schedule.");

                    let scheduleHtml = `<table class="table table-striped table-hover">

                                    <thead>
                                        <tr>
                                            <th>Sched No.</th>
                                            <th>Course No.</th>
                                            <th>Time</th>
                                            <th>Days</th>
                                            <th>Room</th>
                                            <th>Units</th>
                                        </tr>
                                    </thead>
                                    <tbody>`;

                    response.schedule.forEach(item => {
                        scheduleHtml += `<tr>

                                    <td>${item.sched_no}</td>
                                    <td>${item.course_no}</td>
                                    <td>${item.time}</td>
                                    <td>${item.days}</td>
                                    <td>${item.room}</td>
                                    <td>${item.units}</td>
                                </tr>`;
                    });

                    scheduleHtml += `</tbody></table>`;
                    $("#preview-content").html(scheduleHtml);
                } else {
                    $("#ocr-alert").removeClass("d-none alert-success").addClass("alert-danger").text(response.error);
                    $("#upload-step").show();
                    $("#preview-step").hide();
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                $("#ocr-alert").removeClass("d-none alert-success").addClass("alert-danger").text("An unexpected error occurred during processing. Please try again.");
                console.error("AJAX Error: ", textStatus, errorThrown);
            }
        });

    });


    // ================== AJAX ONBOARDING ==================
    $("#viewTipsBtn").click(function () {
        $.get("../../pages/user/get_tips.php", function (data) {
            $("#onboardingContent").html(data);
        });
    });
    $("#restartOnboardingBtn").click(function () {
        $.post("../../pages/user/restart_onboarding.php", { user: "<?= $user['id'] ?? 0 ?>" }, function (data) {
            $("#onboardingContent").html("<div class='alert alert-success'>Onboarding restarted!</div>");
        });

    });

    // Back to upload button functionality
    $("#backToUploadBtn").click(function () {
        $("#preview-step").hide();
        $("#upload-step").show();
        $("#ocr-alert").addClass("d-none");
    });




    // JavaScript for responsive behavior
    document.addEventListener('DOMContentLoaded', function () {
        const sidebarToggle = document.getElementById('sidebarToggle');
        const mainContent = document.querySelector('.main-dashboard-content');
        const screenSizeText = document.getElementById('screenSizeText');

        // Function to update screen size indicator
        function updateScreenSizeIndicator() {
            const width = window.innerWidth;
            if (width <= 767) {
                screenSizeText.textContent = 'Mobile';
            } else if (width >= 768 && width <= 1023) {
                screenSizeText.textContent = 'Tablet';
            } else {
                screenSizeText.textContent = 'Desktop';
            }
        }

        // Toggle sidebar on button click
        sidebarToggle.addEventListener('click', function () {
            const isCollapsed = mainContent.style.marginLeft === '0px' ||
                (window.innerWidth <= 1023 && mainContent.style.marginLeft !== '20%');

            if (isCollapsed) {
                // Expand sidebar
                if (window.innerWidth <= 767) {
                    mainContent.style.marginLeft = '0';
                } else if (window.innerWidth <= 1023) {
                    mainContent.style.marginLeft = '80px';
                } else {
                    mainContent.style.marginLeft = '20%';
                }
            } else {
                // Collapse sidebar
                mainContent.style.marginLeft = '0';
            }
        });

        // Handle window resize
        window.addEventListener('resize', function () {
            updateScreenSizeIndicator();

            // Reset content area based on screen size
            if (window.innerWidth >= 1024) {
                mainContent.style.marginLeft = '20%';
            } else if (window.innerWidth >= 768 && window.innerWidth <= 1023) {
                mainContent.style.marginLeft = '80px';
            } else {
                mainContent.style.marginLeft = '0';
            }
        });

        // Initialize
        updateScreenSizeIndicator();

        // Demo AJAX functionality
        document.getElementById('viewTipsBtn').addEventListener('click', function () {
            document.getElementById('onboardingContent').innerHTML =
                '<div class="alert alert-info">Tips loaded successfully! Here are some helpful tips for using ChronoNav.</div>';
        });

        document.getElementById('restartOnboardingBtn').addEventListener('click', function () {
            document.getElementById('onboardingContent').innerHTML =
                '<div class="alert alert-success">Onboarding restarted successfully!</div>';
        });
    });
</script>

<?php include('../../includes/semantics/footer.php'); ?>