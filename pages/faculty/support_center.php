<?php
// CHRONONAV_WEB_DOSS/pages/faculty/support_center.php
// Faculty support center page - allows faculty to ask questions and get support

require_once '../../config/db_connect.php';
require_once '../../middleware/auth_check_faculty.php';

$page_title = "Support and Ask Question";
$current_page = "support_center";

// Handle form submission for support tickets
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'create_ticket') {
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');

        $user_id = $_SESSION['user']['id'];

        // Validation
        $errors = [];
        if (empty($subject))
            $errors[] = "Subject is required.";
        if (empty($message))
            $errors[] = "Message is required.";

        if (empty($errors)) {
            $stmt = $conn->prepare(
                "INSERT INTO tickets (user_id, subject, message, status, created_at) 
                 VALUES (?, ?, ?, 'open', NOW())"
            );
            $stmt->bind_param("iss", $user_id, $subject, $message);

            if ($stmt->execute()) {
                $message_text = "Support ticket created successfully!";
                $message_type = "success";
                // Clear form
                $subject = $message = "";
            } else {
                $message_text = "Error creating ticket: " . $stmt->error;
                $message_type = "danger";
            }
            $stmt->close();
        } else {
            $message_text = "Error: " . implode(" ", $errors);
            $message_type = "danger";
        }
    }
}

// Fetch user's support tickets
$user_id = $_SESSION['user']['id'];
$tickets = [];
$result = $conn->query(
    "SELECT id, subject, status, created_at FROM tickets 
     WHERE user_id = $user_id ORDER BY created_at DESC"
);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $tickets[] = $row;
    }
}

require_once '../../templates/faculty/header_faculty.php';
require_once '../../templates/faculty/sidenav_faculty.php';
?>

<style>
    :root {
        --primary-dark: #101518;
        --secondary-text: #5c748a;
        --border-color: #e5e7eb;
        --accent-blue: #2e78c6;
        --light-bg: #f9fafb;
        --available-color: #10b981;
        --unavailable-color: #ef4444;

        /* Dark mode variables */
        --dm-bg-primary: #0a0f14;
        --dm-bg-secondary: #121a21;
        --dm-bg-tertiary: #1a2430;
        --dm-text-primary: #e5e8eb;
        --dm-text-secondary: #94a3b8;
        --dm-border-color: #263645;
        --dm-accent-blue: #4a90e2;
        --dm-hover-blue: #1c7dd6;
    }

    body {
        font-family: "Space Grotesk", "Noto Sans", sans-serif;
        background-color: #f8fafb;
        color: #333;
        transition: background-color 0.3s ease, color 0.3s ease;
    }

    body.dark-mode {
        background-color: var(--dm-bg-primary) !important;
        color: var(--dm-text-primary) !important;
    }

    .main-content-wrapper {
        margin-left: 20%;
        padding: 0px 35px;
        font-family: "Space Grotesk", "Noto Sans", sans-serif;
        width: 80%;
        padding: 1rem 0;
        background-color: #ffffff;
        min-height: 100vh;
        transition: background-color 0.3s ease, margin-left 0.3s ease;
    }

    body.dark-mode .main-content-wrapper {
        background-color: var(--dm-bg-primary) !important;
        color: var(--dm-text-primary) !important;
    }

    .support-center-page {
        padding: 1rem 0;
    }

    .support-center-header {
        border-bottom: 1px solid #e8edf3;
        padding: 0.75rem 0;
        margin-bottom: 2rem;
        transition: border-color 0.3s ease;
    }

    body.dark-mode .support-center-header {
        border-bottom-color: var(--dm-border-color);
    }

    .page-title {
        color: #0e151b;
        font-weight: 700;
        letter-spacing: -0.015em;
        margin-bottom: 0;
        transition: color 0.3s ease;
    }

    body.dark-mode .page-title {
        color: var(--dm-text-primary) !important;
    }

    .section-title {
        color: #0e151b;
        font-size: 22px;
        font-weight: 700;
        letter-spacing: -0.015em;
        margin-bottom: 1rem;
        transition: color 0.3s ease;
    }

    body.dark-mode .section-title {
        color: var(--dm-text-primary) !important;
    }

    .btn-light-sm {
        background-color: #e8edf3;
        border: none;
        color: #0e151b;
        font-weight: 500;
        font-size: 0.875rem;
        height: 32px;
        padding: 0.4rem 0.5rem;
        border-radius: 0.5rem;
        transition: all 0.3s ease;
    }

    body.dark-mode .btn-light-sm {
        background-color: var(--dm-bg-tertiary) !important;
        color: var(--dm-text-primary) !important;
        border: 1px solid var(--dm-border-color) !important;
    }

    .btn-light-sm:hover {
        background-color: #dce8f3;
        transform: translateY(-1px);
    }

    body.dark-mode .btn-light-sm:hover {
        background-color: var(--dm-hover-blue) !important;
        color: #FFFFFF !important;
        border-color: var(--dm-accent-blue) !important;
    }

    .support-content-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
        margin-bottom: 2rem;
    }

    @media (max-width: 1200px) {
        .support-content-grid {
            grid-template-columns: 1fr;
        }
    }

    .support-section {
        background-color: #f8fafb;
        border: 1px solid #d1dce6;
        border-radius: 0.5rem;
        padding: 1.5rem;
        transition: all 0.3s ease;
    }

    body.dark-mode .support-section {
        background-color: var(--dm-bg-secondary) !important;
        border: 1px solid var(--dm-border-color) !important;
        color: var(--dm-text-primary) !important;
    }

    .card {
        background-color: #f8fafb;
        border: 1px solid #d1dce6;
        border-radius: 0.5rem;
        transition: all 0.3s ease;
    }

    body.dark-mode .card {
        background-color: var(--dm-bg-secondary) !important;
        border: 1px solid var(--dm-border-color) !important;
    }

    .card-header {
        background-color: #e8edf3;
        border-bottom: 1px solid #d1dce6;
        padding: 1rem 1.5rem;
        color: #0e151b;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    body.dark-mode .card-header {
        background-color: var(--dm-bg-tertiary) !important;
        border-bottom: 1px solid var(--dm-border-color) !important;
        color: var(--dm-text-primary) !important;
    }

    .card-body {
        padding: 1.5rem;
        transition: background-color 0.3s ease;
    }

    body.dark-mode .card-body {
        background-color: var(--dm-bg-secondary);
    }

    .form-label {
        color: #0e151b;
        font-weight: 500;
        font-size: 0.875rem;
        margin-bottom: 0.5rem;
        transition: color 0.3s ease;
    }

    body.dark-mode .form-label {
        color: var(--dm-text-primary) !important;
    }

    .form-control,
    .form-select {
        border: 1px solid #d1dce6;
        border-radius: 0.5rem;
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
        background-color: #ffffff;
        color: #333;
        transition: all 0.3s ease;
    }

    body.dark-mode .form-control,
    body.dark-mode .form-select {
        background-color: var(--dm-bg-tertiary) !important;
        border: 1px solid var(--dm-border-color) !important;
        color: var(--dm-text-primary) !important;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #1d7dd7;
        box-shadow: 0 0 0 0.2rem rgba(29, 125, 215, 0.25);
    }

    body.dark-mode .form-control:focus,
    body.dark-mode .form-select:focus {
        border-color: var(--dm-accent-blue) !important;
        box-shadow: 0 0 0 0.2rem rgba(74, 144, 226, 0.25) !important;
    }

    .btn-primary {
        background-color: #1d7dd7;
        border: none;
        color: #ffffff;
        font-weight: 500;
        font-size: 0.875rem;
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        transition: all 0.3s ease;
    }

    body.dark-mode .btn-primary {
        background-color: var(--dm-accent-blue) !important;
        border: 1px solid var(--dm-accent-blue) !important;
        color: #FFFFFF !important;
    }

    .btn-primary:hover {
        background-color: #166dbb;
        transform: translateY(-2px);
    }

    body.dark-mode .btn-primary:hover {
        background-color: var(--dm-hover-blue) !important;
        border-color: var(--dm-hover-blue) !important;
    }

    .btn-info {
        background-color: #17a2b8;
        border: none;
        color: #ffffff;
        font-weight: 500;
        font-size: 0.875rem;
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        transition: all 0.3s ease;
    }

    body.dark-mode .btn-info {
        background-color: #17a2b8 !important;
        border: 1px solid #17a2b8 !important;
        color: #FFFFFF !important;
    }

    /* Alert Styles */
    .alert {
        border: none;
        border-radius: 0.5rem;
        padding: 1rem 1.25rem;
        margin: 1rem 0;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .alert-success {
        background-color: #d4edda;
        color: #155724;
    }

    body.dark-mode .alert-success {
        background-color: rgba(40, 167, 69, 0.15) !important;
        color: #94ADC7 !important;
        border: 1px solid #28a745 !important;
    }

    .alert-danger {
        background-color: #f8d7da;
        color: #721c24;
    }

    body.dark-mode .alert-danger {
        background-color: rgba(220, 53, 69, 0.15) !important;
        color: #94ADC7 !important;
        border: 1px solid #dc3545 !important;
    }

    .alert-info {
        background-color: #d1ecf1;
        color: #0c5460;
    }

    body.dark-mode .alert-info {
        background-color: rgba(23, 162, 184, 0.15) !important;
        color: #94ADC7 !important;
        border: 1px solid #17a2b8 !important;
    }

    /* Table Styles */
    .table {
        background-color: #ffffff;
        color: #0e151b;
        border-collapse: separate;
        border-spacing: 0;
        transition: all 0.3s ease;
    }

    body.dark-mode .table {
        background-color: var(--dm-bg-secondary) !important;
        color: var(--dm-text-primary) !important;
    }

    .table thead th {
        background-color: #e8edf3;
        border-bottom: 2px solid #d1dce6;
        color: #0e151b;
        font-weight: 600;
        padding: 0.75rem;
        transition: all 0.3s ease;
    }

    body.dark-mode .table thead th {
        background-color: var(--dm-bg-tertiary) !important;
        border-bottom: 2px solid var(--dm-border-color) !important;
        color: var(--dm-text-primary) !important;
    }

    .table tbody td {
        padding: 0.75rem;
        border-top: 1px solid #d1dce6;
        border-bottom: 1px solid #d1dce6;
        transition: all 0.3s ease;
    }

    body.dark-mode .table tbody td {
        border-top: 1px solid var(--dm-border-color) !important;
        border-bottom: 1px solid var(--dm-border-color) !important;
    }

    .table-hover tbody tr:hover {
        background-color: #e8edf3;
        transition: background-color 0.3s ease;
    }

    body.dark-mode .table-hover tbody tr:hover {
        background-color: var(--dm-bg-tertiary) !important;
    }

    /* Status Badges */
    .badge {
        font-size: 0.75rem;
        font-weight: 600;
        padding: 0.35em 0.65em;
        border-radius: 50rem;
        transition: all 0.3s ease;
    }

    .bg-success {
        background-color: #27ae60 !important;
        color: white !important;
    }

    body.dark-mode .bg-success {
        background-color: #27ae60 !important;
        color: #FFFFFF !important;
    }

    .bg-warning {
        background-color: #f39c12 !important;
        color: white !important;
    }

    body.dark-mode .bg-warning {
        background-color: #f39c12 !important;
        color: #000000 !important;
    }

    .bg-info {
        background-color: #17a2b8 !important;
        color: white !important;
    }

    body.dark-mode .bg-info {
        background-color: #17a2b8 !important;
        color: #FFFFFF !important;
    }

    .bg-danger {
        background-color: #dc3545 !important;
        color: white !important;
    }

    body.dark-mode .bg-danger {
        background-color: #dc3545 !important;
        color: #FFFFFF !important;
    }

    /* Scrollbar Styling */
    ::-webkit-scrollbar {
        width: 12px;
        height: 12px;
    }

    ::-webkit-scrollbar-track {
        background: #ffffff;
    }

    body.dark-mode ::-webkit-scrollbar-track {
        background: var(--dm-bg-secondary) !important;
    }

    ::-webkit-scrollbar-thumb {
        background-color: #737373;
        border-radius: 6px;
        border: 3px solid #ffffff;
    }

    body.dark-mode ::-webkit-scrollbar-thumb {
        background-color: var(--dm-text-secondary) !important;
        border: 3px solid var(--dm-bg-secondary) !important;
    }

    ::-webkit-scrollbar-thumb:hover {
        background-color: #2e78c6;
    }

    body.dark-mode ::-webkit-scrollbar-thumb:hover {
        background-color: var(--dm-accent-blue) !important;
    }

    /* Mobile: 767px and below */
    @media (max-width: 767px) {
        .main-content-wrapper {
            margin-left: 0 !important;
            padding: 0px 15px !important;
            width: 100% !important;
        }

        .container-fluid.py-4 {
            padding-left: 1rem !important;
            padding-right: 1rem !important;
        }

        h2.mb-4 {
            font-size: 1.5rem !important;
            text-align: center;
            width: 100%;
            min-width: auto !important;
        }

        .section-title {
            font-size: 1.1rem !important;
            text-align: center;
        }

        .row {
            flex-direction: column !important;
        }

        .col-lg-5,
        .col-lg-7 {
            width: 100% !important;
            max-width: 100% !important;
        }

        .support-section {
            padding: 1rem !important;
            margin-bottom: 1rem !important;
        }

        .card {
            margin-bottom: 1rem !important;
        }

        .card-header h5 {
            font-size: 1rem !important;
            text-align: center;
        }

        .btn-light-sm {
            width: 100% !important;
            justify-content: center;
            margin-top: 0.5rem;
        }

        .form-control,
        .form-select {
            font-size: 16px !important;
        }

        .alert {
            padding: 0.75rem 1rem !important;
            margin: 0.75rem 0 !important;
        }

        .table-responsive {
            margin-top: 0.5rem;
        }

        .table th,
        .table td {
            padding: 0.5rem !important;
            font-size: 0.8rem !important;
        }

        .badge {
            font-size: 0.7rem !important;
            padding: 0.25em 0.5em !important;
        }

        .btn {
            width: 100% !important;
            justify-content: center;
            min-height: 44px;
            display: flex;
            align-items: center;
        }
    }

    /* Tablet: 768px to 1023px */
    @media (min-width: 768px) and (max-width: 1023px) {
        .main-content-wrapper {
            margin-left: 15% !important;
            padding: 0px 25px !important;
            width: 85% !important;
        }

        .container-fluid.py-4 {
            padding-left: 1.5rem !important;
            padding-right: 1.5rem !important;
        }

        h2.mb-4 {
            font-size: 1.75rem !important;
        }

        .section-title {
            font-size: 1.25rem !important;
        }

        .col-lg-5,
        .col-lg-7 {
            width: 100% !important;
        }

        .support-section {
            padding: 1.25rem !important;
        }
    }

    /* Desktop: 1024px and above */
    @media (min-width: 1024px) {
        .main-content-wrapper {
            margin-left: 20% !important;
            padding: 0px 35px !important;
            width: 80% !important;
        }

        .container-fluid.py-4 {
            padding-left: 2rem !important;
            padding-right: 2rem !important;
        }

        h2.mb-4 {
            font-size: 2rem !important;
        }

        .section-title {
            font-size: 1.375rem !important;
        }

        .support-content-grid {
            grid-template-columns: 1fr 1fr !important;
            gap: 2rem !important;
        }

        .support-section {
            padding: 1.5rem !important;
        }
    }

    /* Responsive sidebar adjustments */
    @media (max-width: 1023px) {
        .sidebar-toggle {
            border-radius: 1px;
        }
    }

    /* Ensure proper spacing on all devices */
    @media (max-width: 767px) {
        .support-center-header.my-3 {
            margin-top: 1rem !important;
            margin-bottom: 1rem !important;
        }

        .mb-3 {
            margin-bottom: 1rem !important;
        }

        .mb-4 {
            margin-bottom: 1rem !important;
        }

        .ms-3 {
            margin-left: 0.5rem !important;
        }

        .gap-3 {
            gap: 1rem !important;
        }

        .py-4 {
            padding-top: 1rem !important;
            padding-bottom: 1rem !important;
        }
    }

    /* Better touch targets for mobile */
    @media (max-width: 767px) {
        .accordion-button {
            min-height: 44px;
            display: flex;
            align-items: center;
        }

        .btn {
            min-height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .form-control,
        .form-select {
            min-height: 44px;
        }
    }

    /* Print styles */
    @media print {
        .main-content-wrapper {
            margin-left: 0 !important;
            max-width: 100% !important;
            padding: 0 !important;
        }

        .btn-light-sm,
        .sidebar-toggle,
        .btn-primary,
        .btn-info {
            display: none !important;
        }

        .table {
            border: 1px solid #000 !important;
        }

        .table th,
        .table td {
            border: 1px solid #000 !important;
        }

        body.dark-mode .main-content-wrapper {
            background-color: white !important;
            color: black !important;
        }

        body.dark-mode .table {
            background-color: white !important;
            color: black !important;
        }
    }

    /* Responsive sidebar toggle button */
    .sidebar-toggle {
        display: none;
        position: fixed;
        top: 15px;
        left: 15px;
        z-index: 1100;
        background: #3e99f4;
        color: white;
        border: none;
        border-radius: 5px;
        padding: 8px 12px;
        font-size: 1.2rem;
    }

    @media (max-width: 1023px) {
        .sidebar-toggle {
            display: flex;
            position: fixed;
            right: 1rem;
            left: unset;
            top: 5rem;
            z-index: 1100;
            width: 30px;
            height: 30px;
            background: #f0f2f5;
            color: #111418;
            border: 1px solid #ddd;
            border-radius: 50%;
            cursor: pointer;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, background-color 0.3s ease, right 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        body.dark-mode .sidebar-toggle {
            background: var(--dm-bg-tertiary);
            color: var(--dm-text-primary);
            border: 1px solid var(--dm-border-color);
        }
    }

    /* Ensure proper spacing */
    h2.mb-4 {
        font-weight: 700;
        letter-spacing: -0.015em;
        margin-bottom: 1.5rem !important;
    }

    .card {
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    body.dark-mode .card {
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
    }

    .table th i,
    .table td i {
        margin-right: 0.5rem;
    }

    /* Form field spacing */
    .mb-3 {
        margin-bottom: 1rem !important;
    }

    /* Button spacing */
    .btn {
        margin-top: 0.5rem;
    }

    /* Ticket count styling */
    .card-header h5 {
        margin-bottom: 0;
    }

    /* Text color adjustments */
    .text-muted {
        transition: color 0.3s ease;
    }

    body.dark-mode .text-muted {
        color: var(--dm-text-secondary) !important;
    }

    /* Placeholder text */
    ::placeholder {
        color: #94a3b8;
        transition: color 0.3s ease;
    }

    body.dark-mode ::placeholder {
        color: var(--dm-text-secondary) !important;
    }

    /* For Firefox */
    body.dark-mode input:-moz-placeholder,
    body.dark-mode textarea:-moz-placeholder {
        color: var(--dm-text-secondary) !important;
    }

    /* Table light class for header */
    .table-light {
        transition: all 0.3s ease;
    }

    body.dark-mode .table-light {
        background-color: var(--dm-bg-tertiary) !important;
        color: var(--dm-text-primary) !important;
    }

    /* Text area specific styling */
    textarea.form-control {
        min-height: 150px;
        resize: vertical;
    }

    body.dark-mode textarea.form-control {
        background-color: var(--dm-bg-tertiary);
        color: var(--dm-text-primary);
    }
</style>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<div class="main-content-wrapper">
    <div class="container-fluid py-4">
        <!-- Header Section -->
        <div class="support-center-header my-3">
            <div class="d-flex flex-wrap justify-content-between gap-3 align-items-center">
                <h1 class="page-title fs-3" style="min-width: 288px;">
                    <i class="fas fa-headset me-2"></i><?= htmlspecialchars($page_title) ?>
                </h1>
                <?php if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])): ?>
                    <a href="<?= htmlspecialchars($_SERVER['HTTP_REFERER']) ?>" class="btn-light-sm text-decoration-none">
                        <i class="fas fa-arrow-left me-2"></i>Back
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (isset($message_text)): ?>
            <div class="alert alert-<?= htmlspecialchars($message_type) ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message_text) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="support-content-grid">
            <!-- Create Support Ticket Form -->
            <div class="support-section">
                <h2 class="section-title mb-3">
                    <i class="fas fa-plus-circle me-2"></i>Create Support Ticket
                </h2>
                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="create_ticket">

                            <div class="mb-3">
                                <label for="subject" class="form-label fw-bold">Subject</label>
                                <input type="text" class="form-control" id="subject" name="subject" required
                                    value="<?= isset($subject) ? htmlspecialchars($subject) : '' ?>"
                                    placeholder="Brief description of your issue">
                            </div>

                            <div class="mb-3">
                                <label for="message" class="form-label fw-bold">Detailed Message</label>
                                <textarea class="form-control" id="message" name="message" rows="6" required
                                    placeholder="Please describe your issue in detail"><?= isset($message) ? htmlspecialchars($message) : '' ?></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-paper-plane me-2"></i> Submit Ticket
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- My Support Tickets -->
            <div class="support-section">
                <h2 class="section-title mb-3">
                    <i class="fas fa-list me-2"></i>My Support Tickets (<?= count($tickets) ?>)
                </h2>
                <div class="card">
                    <div class="card-body">
                        <?php if (empty($tickets)): ?>
                            <div class="alert alert-info" role="alert">
                                <i class="fas fa-info-circle me-2"></i> No support tickets yet. Create one to get help!
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th><i class="fas fa-tag"></i> ID</th>
                                            <th><i class="fas fa-heading"></i> Subject</th>
                                            <th><i class="fas fa-check-circle"></i> Status</th>
                                            <th><i class="fas fa-calendar"></i> Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($tickets as $ticket): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($ticket['id']) ?></td>
                                                <td><?= htmlspecialchars($ticket['subject']) ?></td>
                                                <td>
                                                    <span
                                                        class="badge bg-<?= $ticket['status'] === 'closed' ? 'success' : ($ticket['status'] === 'in progress' ? 'warning' : 'info') ?>">
                                                        <?= ucfirst($ticket['status']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php
                                                    $date = new DateTime($ticket['created_at']);
                                                    echo $date->format('M d, Y');
                                                    ?>
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
        </div>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>

<script>
    // Dark mode detection and adjustment
    function checkDarkMode() {
        if (document.body.classList.contains('dark-mode')) {
            document.body.style.backgroundColor = "var(--dm-bg-primary)";
            document.querySelector('.main-content-wrapper').style.backgroundColor = "var(--dm-bg-primary)";
        } else {
            document.body.style.backgroundColor = "#ffffff";
            document.querySelector('.main-content-wrapper').style.backgroundColor = "#ffffff";
        }
    }

    // Check on load
    document.addEventListener('DOMContentLoaded', function () {
        checkDarkMode();

        // Listen for dark mode changes
        const observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                if (mutation.attributeName === 'class') {
                    checkDarkMode();
                }
            });
        });

        observer.observe(document.body, { attributes: true });
    });

    // Favicon setup
    (function () {
        const faviconUrl = "https://res.cloudinary.com/deua2yipj/image/upload/v1758917007/ChronoNav_logo_muon27.png";
        document.querySelectorAll('link[rel="icon"], link[rel="shortcut icon"]').forEach(link => link.remove());
        const link = document.createElement("link");
        link.rel = "icon";
        link.type = "image/png";
        link.href = faviconUrl;
        document.head.appendChild(link);
    })();
</script>