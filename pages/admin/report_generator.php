<?php
// CHRONONAV_WEB_DOSS/pages/admin/report_generator.php
require_once '../../middleware/auth_check.php';
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php'; // Assuming requireRole function is here

// Composer Autoloader (optional - load if available)
if (file_exists('../../vendor/autoload.php')) {
    require_once '../../vendor/autoload.php';
}

// Ensure user is an admin
// The requireRole function handles redirection and messages
requireRole(['admin']);

// Fetch user data for header display (name, role, profile_img)
// This is done here so the variables are available before including the header.
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
        // Construct profile_img_src for the header, relative to the templates/admin directory
        // Assuming uploads/profiles is relative to the project root (CHRONONAV_WEB_DOSS)
        $profile_img_src = (strpos($header_user_data['profile_img'], 'uploads/') === 0) ? '../../' . $header_user_data['profile_img'] : '../../uploads/profiles/default-avatar.png';
    } else {
        // Fallback if user data for header somehow isn't found (shouldn't happen with auth_check)
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

// Set variables for header and sidenav
$page_title = "Report Generator";
$current_page = "report_generator"; // For sidenav highlighting

// Messages will be set in the logic file (if any) or by requireRole and pulled from session
$message = $_SESSION['message'] ?? '';
$message_type = $_SESSION['message_type'] ?? '';
unset($_SESSION['message']);
unset($_SESSION['message_type']);

// Fetch list of faculties for the filter dropdown
$faculties = [];
$sql_faculties = "SELECT id, name FROM users WHERE role = 'faculty' ORDER BY name";
$result_faculties = $conn->query($sql_faculties);
if ($result_faculties && $result_faculties->num_rows > 0) {
    while ($row = $result_faculties->fetch_assoc()) {
        $faculties[] = $row;
    }
}

// Retrieve filter parameters from GET request for display
$filter_faculty_id = $_GET['faculty_id'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// Report Data retrieval logic
$report_data = [];

// Determine the threshold for 'total_expected_sessions' based on end_date filter
// Using prepared statement for condition as well for safety
$expected_sessions_end_condition = "CONCAT(cs_sub.session_date, ' ', cs_sub.actual_end_time) <= NOW()"; // Default to past sessions up to now
$expected_sessions_end_param = null;
$expected_sessions_end_type = '';

if (!empty($end_date)) {
    // If end_date is provided, use it for the condition
    // WARNING: This assumes that $end_date is already safe for direct embedding
    // in the subquery's SQL string if it's not being bound.
    // If you intend this to be a bound parameter, it needs to be handled carefully
    // with subqueries (e.g., using variables in SQL or running separate prepares).
    // For now, I'll proceed with your original logic's assumption.
    $expected_sessions_end_condition = "CONCAT(cs_sub.session_date, ' 23:59:59') <= '" . $conn->real_escape_string($end_date) . "'";
    // If it was meant to be bound, the condition would be `?` and you'd add to main params for binding.
}

// SQL query to fetch report data
$sql = "
    SELECT
        u.name AS faculty_name,
        u.id AS faculty_id,
        c.class_id,
        c.class_code,
        c.class_name,
        c.semester,
        c.academic_year,
        COUNT(DISTINCT cs.id) AS total_sessions_recorded,
        (SELECT COUNT(DISTINCT student_id) FROM class_students WHERE class_id = c.class_id) AS total_students_in_class,
        SUM(CASE WHEN ar.status = 'Present' THEN 1 ELSE 0 END) AS total_present,
        SUM(CASE WHEN ar.status = 'Absent' THEN 1 ELSE 0 END) AS total_absent,
        SUM(CASE WHEN ar.status = 'Late' THEN 1 ELSE 0 END) AS total_late,
        SUM(CASE WHEN ar.status IS NOT NULL THEN 1 ELSE 0 END) AS total_attendance_marked,
        (SELECT COUNT(cs_sub.id) FROM class_sessions cs_sub WHERE cs_sub.class_id = c.class_id AND {$expected_sessions_end_condition}) AS total_expected_sessions
    FROM
        users u
    LEFT JOIN
        classes c ON u.id = c.faculty_id
    LEFT JOIN
        class_sessions cs ON c.class_id = cs.class_id
    LEFT JOIN
        attendance_record ar ON cs.id = ar.session_id
    WHERE
        u.role = 'faculty'
";

$params = [];
$types = '';

if (!empty($filter_faculty_id)) {
    $sql .= " AND u.id = ?";
    $params[] = $filter_faculty_id;
    $types .= 'i';
}

if (!empty($start_date)) {
    $sql .= " AND cs.session_date >= ?";
    $params[] = $start_date;
    $types .= 's';
}
if (!empty($end_date)) {
    $sql .= " AND cs.session_date <= ?";
    $params[] = $end_date;
    $types .= 's';
}

$sql .= " GROUP BY u.id, u.name, c.class_id, c.class_code, c.class_name, c.semester, c.academic_year
          ORDER BY u.name, c.class_name";

// Prepare and execute the main report query
$stmt_report = $conn->prepare($sql);
if ($stmt_report === false) {
    error_log("Error preparing report query: " . $conn->error);
} else {
    if (!empty($params)) {
        // Need to use call_user_func_array for dynamic parameters with bind_param
        $ref_params = [];
        foreach ($params as $key => $value) {
            $ref_params[$key] = &$params[$key]; // Pass by reference
        }
        call_user_func_array([$stmt_report, 'bind_param'], array_merge([$types], $ref_params));
    }
    $stmt_report->execute();
    $result_report = $stmt_report->get_result();
    while ($row = $result_report->fetch_assoc()) {
        $report_data[] = $row;
    }
    $stmt_report->close();
}

// --- START HTML STRUCTURE ---
// Include the admin header which contains <head> and opening <body> tags
require_once '../../templates/admin/header_admin.php';
require_once '../../templates/admin/sidenav_admin.php'; // Sidenav is included here
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<link rel="stylesheet" href="../../assets/css/admin_css/report_generator.css">

<style>
    body {
        padding-top: 0px;
        background-color: #ffffff;
        font-family: "Space Grotesk", "Noto Sans", sans-serif;
    }

    .main-content-wrapper {
        margin-left: 20%;
        padding: 20px 35px;
        min-height: 100vh;
        background-color: #ffffff;
    }

    /* Header styling */
    h2 {
        font-size: 28px;
        font-weight: bold;
        color: #101518;
        margin-bottom: 20px;
    }

    h4 {
        font-size: 20px;
        font-weight: 600;
        color: #101518;
        margin-bottom: 20px;
    }

    /* Card styling */
    .card {
        border: none;
        border-radius: 0.75rem;
        background: white;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        margin-bottom: 25px;
    }

    .card.p-4 {
        padding: 25px !important;
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

    .form-select,
    .form-control {
        border-radius: 0.5rem;
        border: 1px solid #d1d5db;
        padding: 10px 12px;
        font-size: 14px;
        color: #101518;
        background-color: white;
    }

    .form-select:focus,
    .form-control:focus {
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
    }

    .btn-primary {
        background-color: #2e78c6;
        color: white;
    }

    .btn-primary:hover {
        background-color: #2563eb;
        transform: translateY(-1px);
    }

    .btn-danger {
        background-color: #dc3545;
        color: white;
    }

    .btn-danger:hover {
        background-color: #c82333;
        transform: translateY(-1px);
    }

    /* Table styling */
    .table-responsive {
        border-radius: 0.75rem;
        overflow: hidden;
        border: 1px solid #e5e7eb;
    }

    .report-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .report-table thead th {
        background-color: #eaedf1;
        color: #101518;
        font-weight: 600;
        font-size: 14px;
        padding: 16px 12px;
        border-bottom: 2px solid #d1d5db;
        text-align: left;
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .report-table tbody td {
        padding: 14px 12px;
        font-size: 14px;
        color: #374151;
        border-bottom: 1px solid #f1f3f4;
    }

    .report-table tbody tr {
        transition: background-color 0.2s ease;
    }

    .report-table tbody tr:hover {
        background-color: #f9fafb;
    }

    .report-table tbody tr:last-child td {
        border-bottom: none;
    }

    /* Filter section styling */
    .report-filters .row {
        margin-bottom: 0;
    }

    .report-filters .col-md-2 {
        display: flex;
        align-items: flex-end;
    }

    /* Header actions */
    .d-flex.justify-content-between.align-items-center {
        margin-bottom: 20px;
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
        .main-content-wrapper {
            margin-left: 0;
            padding: 15px;
        }

        h2 {
            font-size: 22px;
        }

        h4 {
            font-size: 18px;
        }

        .card.p-4 {
            padding: 20px !important;
        }

        .report-filters .row {
            gap: 15px;
        }

        .report-filters .col-md-4,
        .report-filters .col-md-3,
        .report-filters .col-md-2 {
            width: 100%;
        }

        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .report-table thead th,
        .report-table tbody td {
            padding: 12px 8px;
            font-size: 13px;
        }

        .d-flex.justify-content-between.align-items-center {
            flex-direction: column;
            gap: 15px;
            align-items: flex-start;
        }

        .btn {
            width: 100%;
            justify-content: center;
        }
    }

    @media (min-width: 768px) and (max-width: 1023px) {
        .main-content-wrapper {
            margin-left: 80px;
            padding: 20px 25px;
        }

        h2 {
            font-size: 24px;
        }

        h4 {
            font-size: 19px;
        }

        .report-table thead th,
        .report-table tbody td {
            padding: 14px 10px;
            font-size: 13.5px;
        }
    }

    @media (min-width: 1024px) {
        .main-content-wrapper {
            margin-left: 20%;
            padding: 20px 35px;
        }
    }

    /* Additional utility classes */
    .text-center {
        text-align: center;
    }

    .m-0 {
        margin: 0;
    }

    .mb-3 {
        margin-bottom: 20px !important;
    }

    .mb-4 {
        margin-bottom: 25px !important;
    }

    .py-4 {
        padding-top: 25px;
        padding-bottom: 25px;
    }

    /* Icon styling */
    .fas {
        font-size: 16px;
    }

    .me-2 {
        margin-right: 8px;
    }

    /* Table row highlighting for percentages */
    .report-table tbody td:contains("%") {
        font-weight: 600;
    }

    /* Percentage colors */
    .attendance-high {
        color: #28a745;
        font-weight: 600;
    }

    .attendance-medium {
        color: #ffc107;
        font-weight: 600;
    }

    .attendance-low {
        color: #dc3545;
        font-weight: 600;
    }




    /* ====================================================================== */
/* Dark Mode Overrides for Report Generator Page                          */
/* ====================================================================== */
body.dark-mode {
    background-color: #121A21 !important;
    color: #E5E8EB !important;
}

body.dark-mode .main-content-wrapper {
    background-color: #121A21 !important;
    color: #E5E8EB !important;
}

/* Header styling */
body.dark-mode h2 {
    color: #E5E8EB !important;
}

body.dark-mode h4 {
    color: #E5E8EB !important;
}

/* Card styling */
body.dark-mode .card {
    background-color: #263645 !important;
    border: 1px solid #121A21 !important;
    color: #E5E8EB !important;
}

body.dark-mode .shadow-sm {
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15) !important;
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
body.dark-mode .form-control {
    background-color: #121A21 !important;
    border: 1px solid #263645 !important;
    color: #E5E8EB !important;
}

body.dark-mode .form-select:focus,
body.dark-mode .form-control:focus {
    background-color: #121A21 !important;
    border-color: #1C7DD6 !important;
    box-shadow: 0 0 0 3px rgba(28, 125, 214, 0.25) !important;
    color: #E5E8EB !important;
}

body.dark-mode .form-select option {
    background-color: #263645 !important;
    color: #E5E8EB !important;
}

/* Button styling */
body.dark-mode .btn-primary {
    background-color: #1C7DD6 !important;
    color: #FFFFFF !important;
    border: 1px solid #1C7DD6 !important;
}

body.dark-mode .btn-primary:hover {
    background-color: #2E78C6 !important;
    border-color: #2E78C6 !important;
    transform: translateY(-1px);
}

body.dark-mode .btn-danger {
    background-color: #dc3545 !important;
    color: #FFFFFF !important;
    border: 1px solid #dc3545 !important;
}

body.dark-mode .btn-danger:hover {
    background-color: #c82333 !important;
    border-color: #c82333 !important;
    transform: translateY(-1px);
}

/* Table styling */
body.dark-mode .table-responsive {
    border: 1px solid #121A21 !important;
}

body.dark-mode .report-table {
    background-color: #263645 !important;
    color: #E5E8EB !important;
}

body.dark-mode .report-table thead th {
    background-color: #121A21 !important;
    color: #E5E8EB !important;
    border-bottom: 2px solid #263645 !important;
}

body.dark-mode .report-table tbody td {
    color: #E5E8EB !important;
    border-bottom: 1px solid #121A21 !important;
    background-color: #263645 !important;
}

body.dark-mode .report-table tbody tr:hover {
    background-color: #1a2635 !important;
}

body.dark-mode .report-table tbody tr:hover td {
    background-color: #1a2635 !important;
}

body.dark-mode .table-bordered {
    border: 1px solid #121A21 !important;
}

body.dark-mode .table-bordered th,
body.dark-mode .table-bordered td {
    border: 1px solid #121A21 !important;
}

/* Table row striping */
body.dark-mode .table-striped tbody tr:nth-of-type(odd) {
    background-color: #1a2635 !important;
}

body.dark-mode .table-striped tbody tr:nth-of-type(odd) td {
    background-color: #1a2635 !important;
}

/* Scrollbar styling for dark mode */
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

/* Percentage colors for attendance */
body.dark-mode .attendance-high {
    color: #28a745 !important;
    font-weight: 600;
}

body.dark-mode .attendance-medium {
    color: #ffc107 !important;
    font-weight: 600;
}

body.dark-mode .attendance-low {
    color: #dc3545 !important;
    font-weight: 600;
}

/* Close button for alerts */
body.dark-mode .btn-close {
    filter: invert(1) grayscale(100%) brightness(200%) !important;
}

/* Responsive adjustments */
@media (max-width: 767px) {
    body.dark-mode .main-content-wrapper {
        background-color: #121A21 !important;
    }
    
    body.dark-mode .card.p-4 {
        background-color: #263645 !important;
    }
}

@media (min-width: 768px) and (max-width: 1023px) {
    body.dark-mode .main-content-wrapper {
        background-color: #121A21 !important;
    }
}

@media (min-width: 1024px) {
    body.dark-mode .main-content-wrapper {
        background-color: #121A21 !important;
    }
}

/* Text selection */
body.dark-mode ::selection {
    background-color: #1C7DD6 !important;
    color: #FFFFFF !important;
}

body.dark-mode ::-moz-selection {
    background-color: #1C7DD6 !important;
    color: #FFFFFF !important;
}

/* Link styling */
body.dark-mode a {
    color: #1C7DD6 !important;
}

body.dark-mode a:hover {
    color: #2E78C6 !important;
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

/* PDF export button icon */
body.dark-mode .btn-danger .fas.fa-file-pdf {
    color: inherit !important;
}

/* Date input placeholder text */
body.dark-mode input[type="date"]::-webkit-calendar-picker-indicator {
    filter: invert(1);
}

/* Table header sticky position styling */
body.dark-mode .report-table thead th {
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

/* Empty state styling */
body.dark-mode .alert-info.text-center {
    background-color: rgba(28, 125, 214, 0.1) !important;
    color: #94ADC7 !important;
    border: 1px solid rgba(28, 125, 214, 0.3) !important;
}

/* Filter section background */
body.dark-mode .report-filters {
    background-color: transparent !important;
}

/* Form control placeholder text */
body.dark-mode .form-control::placeholder {
    color: #94ADC7 !important;
}

/* Table cell text colors */
body.dark-mode .report-table tbody td {
    color: #94ADC7 !important;
}

body.dark-mode .report-table tbody td:first-child {
    color: #E5E8EB !important;
    font-weight: 500;
}

/* Icon colors */
body.dark-mode .fas {
    color: inherit;
}

/* Print styles for dark mode */
@media print {
    body.dark-mode {
        background-color: white !important;
        color: black !important;
    }
    
    body.dark-mode .card,
    body.dark-mode .report-table,
    body.dark-mode .report-table th,
    body.dark-mode .report-table td {
        background-color: white !important;
        color: black !important;
        border-color: #000 !important;
    }
    
    body.dark-mode .btn-danger,
    body.dark-mode .btn-primary {
        display: none !important;
    }
}

/* Focus states for accessibility */
body.dark-mode .btn:focus {
    box-shadow: 0 0 0 0.2rem rgba(28, 125, 214, 0.5) !important;
}

body.dark-mode .form-control:focus,
body.dark-mode .form-select:focus {
    box-shadow: 0 0 0 0.2rem rgba(28, 125, 214, 0.25) !important;
}

/* Container background */
body.dark-mode .container-fluid.py-4 {
    background-color: transparent !important;
}

/* Text color utilities */
body.dark-mode .text-center {
    color: #E5E8EB !important;
}
</style>

<div class="main-content-wrapper">
    <div class="container-fluid py-4">
        <h2 class="mb-4"><?= $page_title ?></h2>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= htmlspecialchars($message_type) ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card p-4 shadow-sm mb-4">
            <h4>Filter Report</h4>
            <form action="" method="GET" class="report-filters">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="faculty_id" class="form-label">Filter by Faculty:</label>
                        <select class="form-select" id="faculty_id" name="faculty_id">
                            <option value="">All Faculty</option>
                            <?php foreach ($faculties as $faculty): ?>
                                <option value="<?php echo htmlspecialchars($faculty['id']); ?>"
                                    <?php echo ($filter_faculty_id == $faculty['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($faculty['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="start_date" class="form-label">Start Date:</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="end_date" class="form-label">End Date:</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Apply Filter</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="card p-4 shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="m-0">Report Data</h4>
                <a href="../../actions/admin/export_report_pdf.php?faculty_id=<?php echo htmlspecialchars($filter_faculty_id); ?>&start_date=<?php echo htmlspecialchars($start_date); ?>&end_date=<?php echo htmlspecialchars($end_date); ?>" class="btn btn-danger">
                    <i class="fas fa-file-pdf me-2"></i> Export to PDF
                </a>
            </div>
            <?php if (empty($report_data)): ?>
                <div class="alert alert-info text-center">No report data available for the selected filters.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped report-table">
                        <thead>
                            <tr>
                                <th>Faculty Name</th>
                                <th>Class Code</th>
                                <th>Class Name</th>
                                <th>Semester (Year)</th>
                                <th>Recorded Sessions</th>
                                <th>Expected Sessions (Past)</th>
                                <th>Attendance Marked (%)</th>
                                <th>Students Enrolled</th>
                                <th>Present</th>
                                <th>Absent</th>
                                <th>Late</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_data as $row): ?>
                                <?php
                                $attendance_percentage = 0;
                                if ($row['total_students_in_class'] > 0 && $row['total_sessions_recorded'] > 0) {
                                    $possible_records = $row['total_students_in_class'] * $row['total_sessions_recorded'];
                                    if ($possible_records > 0) {
                                        $attendance_percentage = ($row['total_attendance_marked'] / $possible_records) * 100;
                                    }
                                }
                                
                                // Determine attendance percentage class
                                $attendance_class = '';
                                if ($attendance_percentage >= 80) {
                                    $attendance_class = 'attendance-high';
                                } elseif ($attendance_percentage >= 60) {
                                    $attendance_class = 'attendance-medium';
                                } elseif ($attendance_percentage > 0) {
                                    $attendance_class = 'attendance-low';
                                }
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['faculty_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['class_code']); ?></td>
                                    <td><?php echo htmlspecialchars($row['class_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['semester'] ?? 'N/A') . ' (' . htmlspecialchars($row['academic_year'] ?? 'N/A') . ')'; ?></td>
                                    <td><?php echo htmlspecialchars($row['total_sessions_recorded']); ?></td>
                                    <td><?php echo htmlspecialchars($row['total_expected_sessions']); ?></td>
                                    <td class="<?= $attendance_class ?>"><?php echo number_format($attendance_percentage, 2); ?>%</td>
                                    <td><?php echo htmlspecialchars($row['total_students_in_class']); ?></td>
                                    <td><?php echo htmlspecialchars($row['total_present']); ?></td>
                                    <td><?php echo htmlspecialchars($row['total_absent']); ?></td>
                                    <td><?php echo htmlspecialchars($row['total_late']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="../../assets/js/jquery.min.js"></script>
<script src="../../assets/js/script.js"></script>

<script>
    // Add percentage color highlighting dynamically
    document.addEventListener('DOMContentLoaded', function() {
        const percentageCells = document.querySelectorAll('.report-table td');
        percentageCells.forEach(cell => {
            if (cell.textContent.includes('%')) {
                const percentage = parseFloat(cell.textContent);
                if (percentage >= 80) {
                    cell.classList.add('attendance-high');
                } else if (percentage >= 60) {
                    cell.classList.add('attendance-medium');
                } else if (percentage > 0) {
                    cell.classList.add('attendance-low');
                }
            }
        });
    });
</script>

<?php
// Include the common footer which closes <body> and <html> and includes common JS
include_once '../../templates/footer.php';
?>