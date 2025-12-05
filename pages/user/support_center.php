<?php
// CHRONONAV_WEBZD/pages/user/support_center.php

require_once '../../middleware/auth_check.php';
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php'; // Assuming functions.php exists for common functions

// Enforce user access for this page
requireRole(['user']); // Only 'user' role is allowed here. If 'admin' also needs access, adjust requireRole in functions.php or here.

$user = $_SESSION['user'];
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
        error_log("Security Alert: User ID {$user_id} in session not found in database for support_center (user).");
        session_destroy();
        header('Location: ../../auth/login.php?error=user_not_found');
        exit();
    }
    $stmt_user_data->close();
} else {
    error_log("Database query preparation failed for support_center (user): " . $conn->error);
    // Optionally redirect or show a user-friendly error
}

// Prepare variables for header display
$display_username = htmlspecialchars($user['name'] ?? 'Guest');
$display_user_role = htmlspecialchars(ucfirst($user['role'] ?? 'User'));

// Determine the correct profile image source path for the header
$display_profile_img = htmlspecialchars($user['profile_img'] ?? 'uploads/profiles/default-avatar.png');
$profile_img_src = (strpos($display_profile_img, 'uploads/') === 0) ? '../../' . $display_profile_img : $display_profile_img;


$page_title = "Help & Support Center";
$current_page = "feedback"; // Or whatever sidebar item corresponds to user support

$message = '';
$message_type = '';

// Initialize variables for form (in case of validation errors)
$subject = '';
$message_content = '';


// --- Handle Ticket Submission (User Submission) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_ticket'])) {
    $subject = trim($_POST['ticket_subject'] ?? '');
    $message_content = trim($_POST['ticket_message'] ?? '');

    if (empty($subject) || empty($message_content)) {
        $message = "Please fill in all fields for the ticket.";
        $message_type = 'danger';
    } else {
        $stmt = $conn->prepare("INSERT INTO tickets (user_id, subject, message, status) VALUES (?, ?, ?, 'open')");
        if ($stmt) {
            $stmt->bind_param("iss", $user_id, $subject, $message_content);
            if ($stmt->execute()) {
                $message = "Your ticket has been submitted successfully! We will get back to you shortly.";
                $message_type = 'success';
                // Clear form fields on successful submission
                $subject = '';
                $message_content = '';
            } else {
                $message = "Error submitting your ticket: " . $stmt->error;
                $message_type = 'danger';
            }
            $stmt->close();
        } else {
            $message = "Database error preparing ticket submission: " . $conn->error;
            $message_type = 'danger';
        }
    }
}

// --- Fetch User's FAQs ---
$faqs = [];
$stmt_faq = $conn->prepare("SELECT question, answer FROM faqs ORDER BY id ASC");
if ($stmt_faq) {
    $stmt_faq->execute();
    $result_faq = $stmt_faq->get_result();
    while ($row = $result_faq->fetch_assoc()) {
        $faqs[] = $row;
    }
    $stmt_faq->close();
} else {
    // Error handling for FAQs fetch (consider logging this, but not crucial to show user)
    error_log("Error fetching FAQs: " . $conn->error);
}


// --- Fetch User's Tickets ---
$user_tickets = [];
if ($user_id) {
    $stmt_user_tickets = $conn->prepare("SELECT * FROM tickets WHERE user_id = ? ORDER BY created_at DESC");
    if ($stmt_user_tickets) {
        $stmt_user_tickets->bind_param("i", $user_id);
        $stmt_user_tickets->execute();
        $result_user_tickets = $stmt_user_tickets->get_result();
        while ($row = $result_user_tickets->fetch_assoc()) {
            $user_tickets[] = $row;
        }
        $stmt_user_tickets->close();
    } else {
        $message = "Error fetching your tickets: " . $conn->error;
        $message_type = 'danger';
    }
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
        padding: 10px 35px 20px;
    }

    .main-dashboard-content-wrapper {
        font-family: "Space Grotesk", "Noto Sans", sans-serif;
        min-height: 100vh;
        padding-top: 20px;
    }

    .dashboard-header h1 {
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

    .accordion-button {
        background-color: #f8fafb;
        color: #0e151b;
        font-weight: 500;
        border: none;
        padding: 1rem 1.25rem;
    }

    .accordion-button:not(.collapsed) {
        background-color: #f8fafb;
        color: #0e151b;
        box-shadow: none;
    }

    .accordion-button:focus {
        box-shadow: none;
        border-color: #d1dce6;
    }

    .accordion-body {
        background-color: #ffffff;
        border-top: 1px solid #d1dce6;
    }

    .badge {
        font-size: 0.75rem;
        font-weight: 600;
        padding: 0.375rem 0.75rem;
        border-radius: 0.375rem;
    }

    .status-badge {
        background-color: #1d7dd7 !important;
    }

    .status-open {
        background-color: #1d7dd7 !important;
    }

    .status-closed {
        background-color: #198754 !important;
    }

    .status-pending {
        background-color: #ffc107 !important;
        color: #000 !important;
    }

    .admin-reply {
        background-color: #f8fafb !important;
        border: 1px solid #d1dce6;
        border-radius: 8px;
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

        .dashboard-header h1 {
            font-size: 22px;
        }

        .card-title {
            font-size: 18px;
        }

        .support-section {
            padding: 1rem !important;
        }

        .accordion-button {
            padding: 0.75rem 1rem;
            font-size: 0.9rem;
        }

        .badge {
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
        }
    }



    /* ====================================================================== */
    /* Dark Mode Overrides for Support Center - Custom Colors                */
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
    body.dark-mode .dashboard-header h2 {
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

    body.dark-mode .support-section {
        background-color: #263645 !important;
        border: 1px solid #121A21 !important;
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

    /* Accordion */
    body.dark-mode .accordion-button {
        background-color: #121A21 !important;
        /* Primary dark */
        color: #E5E8EB !important;
        /* Light text */
        border: 1px solid #263645 !important;
    }

    body.dark-mode .accordion-button:not(.collapsed) {
        background-color: #121A21 !important;
        color: #1C7DD6 !important;
        /* Blue for expanded items */
        border-bottom: 1px solid #263645 !important;
    }

    body.dark-mode .accordion-button:focus {
        border-color: #1C7DD6 !important;
        box-shadow: 0 0 0 2px rgba(28, 125, 214, 0.2) !important;
    }

    body.dark-mode .accordion-body {
        background-color: #121A21 !important;
        /* Primary dark */
        border-top: 1px solid #263645 !important;
        color: #E5E8EB !important;
    }

    /* Badges */
    body.dark-mode .badge {
        background-color: #121A21 !important;
        /* Primary dark */
        color: #94ADC7 !important;
        /* Secondary text */
        border: 1px solid #263645 !important;
    }

    body.dark-mode .status-badge,
    body.dark-mode .status-open {
        background-color: #1C7DD6 !important;
        /* Active blue for open tickets */
        color: #FFFFFF !important;
        /* White text */
    }

    body.dark-mode .status-closed {
        background-color: #1B5E20 !important;
        /* Dark green for closed tickets */
        color: #C8E6C9 !important;
        /* Light green text */
    }

    body.dark-mode .status-pending {
        background-color: #F57C00 !important;
        /* Dark orange for pending */
        color: #FFE0B2 !important;
        /* Light orange text */
    }

    /* Admin reply section */
    body.dark-mode .admin-reply {
        background-color: #121A21 !important;
        /* Primary dark */
        border: 1px solid #263645 !important;
        color: #E5E8EB !important;
    }

    body.dark-mode .admin-reply h6 {
        color: #1C7DD6 !important;
        /* Blue for admin reply title */
    }

    body.dark-mode .admin-reply small {
        color: #94ADC7 !important;
        /* Secondary text for timestamp */
    }

    /* Ticket items */
    body.dark-mode .ticket-item {
        border: 1px solid #263645 !important;
        margin-bottom: 0.5rem;
        border-radius: 0.375rem;
    }

    body.dark-mode .ticket-item:hover {
        border-color: #1C7DD6 !important;
        /* Blue border on hover */
    }

    /* FAQ section specific */
    body.dark-mode .faqs-section .accordion-item {
        background-color: #121A21 !important;
        border: 1px solid #263645 !important;
    }

    body.dark-mode .faqs-section .accordion-button:hover {
        background-color: #263645 !important;
        /* Secondary dark on hover */
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

    /* Text colors */
    body.dark-mode .text-muted {
        color: #94ADC7 !important;
        /* Secondary text instead of muted */
    }

    body.dark-mode strong {
        color: #E5E8EB !important;
        /* Light text for strong elements */
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
    body.dark-mode .fa-edit,
    body.dark-mode .fa-history,
    body.dark-mode .fa-question-circle,
    body.dark-mode .fa-paper-plane {
        color: #94ADC7 !important;
        /* Secondary color for icons */
    }

    body.dark-mode .btn:hover .fa-edit,
    body.dark-mode .btn:hover .fa-history,
    body.dark-mode .btn:hover .fa-question-circle,
    body.dark-mode .btn:hover .fa-paper-plane {
        color: #FFFFFF !important;
        /* White icons on hover */
    }

    /* Close button in alerts */
    body.dark-mode .btn-close {
        filter: invert(1) grayscale(100%) brightness(200%) !important;
    }

    /* Responsive adjustments for dark mode */
    @media (max-width: 768px) {
        body.dark-mode .main-dashboard-content {
            background-color: #121A21 !important;
        }

        body.dark-mode .support-section {
            background-color: #263645 !important;
        }

        body.dark-mode .accordion-button {
            background-color: #121A21 !important;
        }
    }

    /* Border utilities */
    body.dark-mode .border-0 {
        border-color: #263645 !important;
        /* Secondary border instead of removing */
    }

    /* Focus states for accessibility */
    body.dark-mode .btn:focus,
    body.dark-mode .form-control:focus,
    body.dark-mode .accordion-button:focus,
    body.dark-mode a:focus {
        outline: 2px solid #1C7DD6 !important;
        outline-offset: 2px;
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

    /* Loading states */
    body.dark-mode .loading {
        background-color: rgba(18, 26, 33, 0.9) !important;
        color: #E5E8EB !important;
    }

    /* Ticket status indicator */
    body.dark-mode .ticket-status-open .accordion-button {
        border-left: 3px solid #1C7DD6 !important;
        /* Blue left border for open tickets */
    }

    body.dark-mode .ticket-status-closed .accordion-button {
        border-left: 3px solid #1B5E20 !important;
        /* Green left border for closed tickets */
    }

    body.dark-mode .ticket-status-pending .accordion-button {
        border-left: 3px solid #F57C00 !important;
        /* Orange left border for pending tickets */
    }

    /* Hover effects for FAQ items */
    body.dark-mode .faqs-section .accordion-item:hover {
        border-color: #1C7DD6 !important;
        transition: border-color 0.2s ease;
    }
</style>

<!-- Favicon -->
<link rel="icon" type="image/x-icon"
    href="https://res.cloudinary.com/deua2yipj/image/upload/v1758917007/ChronoNav_logo_muon27.png">

<?php include('../../includes/semantics/head.php'); ?>

<div class="main-dashboard-content-wrapper">
    <div class="main-dashboard-content">
        <!-- Header Section -->
        <div class="dashboard-header px-3 pt-2 pb-2">
            <h2 class="fs-3 fw-bold"><?= $page_title ?></h2>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?> alert-dismissible fade show mx-3" role="alert">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="user-support-container">
            <div class="support-section card p-4 mb-4 shadow border-0">
                <h2 class="card-title mb-4 d-flex align-items-center gap-2">
                    <i class="fas fa-edit"></i> Submit a New Support Ticket
                </h2>
                <form action="support_center.php" method="POST">
                    <div class="mb-3">
                        <label for="ticket_subject" class="form-label">Subject</label>
                        <input type="text" class="form-control" id="ticket_subject" name="ticket_subject"
                            value="<?= htmlspecialchars($subject) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="ticket_message" class="form-label">Your Message</label>
                        <textarea class="form-control" id="ticket_message" name="ticket_message" rows="5"
                            placeholder="Describe your issue or question here..."
                            required><?= htmlspecialchars($message_content) ?></textarea>
                    </div>
                    <button type="submit" name="submit_ticket"
                        class="btn btn-primary d-flex align-items-center gap-2 mt-3">
                        <i class="fas fa-paper-plane"></i> Submit Ticket
                    </button>
                </form>
            </div>

            <div class="support-section card p-4 mb-4 shadow border-0">
                <h2 class="card-title mb-4 d-flex align-items-center gap-2">
                    <i class="fas fa-history"></i> Your Support Tickets History
                </h2>
                <?php if (!empty($user_tickets)): ?>
                    <div class="accordion" id="userTicketAccordion">
                        <?php foreach ($user_tickets as $ticket): ?>
                            <div
                                class="accordion-item ticket-item ticket-status-<?= htmlspecialchars(str_replace(' ', '-', $ticket['status'])) ?>">
                                <div class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#userTicketCollapse<?= $ticket['id'] ?>" aria-expanded="false"
                                        aria-controls="userTicketCollapse<?= $ticket['id'] ?>">
                                        <div class="d-flex justify-content-between align-items-center w-100">
                                            <div>
                                                Ticket #<?= htmlspecialchars($ticket['id']) ?>:
                                                <?= htmlspecialchars($ticket['subject']) ?>
                                            </div>
                                            <span
                                                class="badge ms-3 status-badge status-<?= htmlspecialchars(str_replace(' ', '-', $ticket['status'])) ?>"><?= htmlspecialchars(ucfirst($ticket['status'])) ?></span>
                                        </div>
                                    </button>
                                </div>
                                <div id="userTicketCollapse<?= $ticket['id'] ?>" class="accordion-collapse collapse"
                                    aria-labelledby="userTicketHeading<?= $ticket['id'] ?>"
                                    data-bs-parent="#userTicketAccordion">
                                    <div class="accordion-body">
                                        <p><strong>Submitted:</strong> <?= htmlspecialchars($ticket['created_at']) ?></p>
                                        <p><strong>Your Message:</strong><br><?= nl2br(htmlspecialchars($ticket['message'])) ?>
                                        </p>

                                        <?php if (!empty($ticket['admin_reply'])): ?>
                                            <div class="admin-reply p-3 mt-3 rounded">
                                                <h6>Admin Reply:</h6>
                                                <p><?= nl2br(htmlspecialchars($ticket['admin_reply'])) ?></p>
                                                <small>Replied: <?= htmlspecialchars($ticket['updated_at']) ?></small>
                                            </div>
                                        <?php else: ?>
                                            <p class="text-muted mt-3">No admin reply yet.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mb-0">You have not submitted any support tickets yet.</div>
                <?php endif; ?>
            </div>

            <div class="support-section faqs-section card p-4 shadow border-0">
                <h2 class="card-title mb-4 d-flex align-items-center gap-2">
                    <i class="fas fa-question-circle"></i> Frequently Asked Questions
                </h2>
                <?php if (!empty($faqs)): ?>
                    <div class="accordion" id="userFaqAccordion">
                        <?php foreach ($faqs as $index => $faq): ?>
                            <div class="accordion-item">
                                <div class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#userFaqCollapse<?= $index ?>" aria-expanded="false"
                                        aria-controls="userFaqCollapse<?= $index ?>">
                                        <div class="d-flex justify-content-between align-items-center w-100">
                                            <?= htmlspecialchars($faq['question']) ?>
                                        </div>
                                    </button>
                                </div>
                                <div id="userFaqCollapse<?= $index ?>" class="accordion-collapse collapse"
                                    aria-labelledby="userFaqHeading<?= $index ?>" data-bs-parent="#userFaqAccordion">
                                    <div class="accordion-body">
                                        <p><?= nl2br(htmlspecialchars($faq['answer'])) ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mb-0">No FAQs available at the moment.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../templates/common/onboarding_modal.php'; ?>

<!-- Bootstrap JS Bundle with Popper -->
<script src="../../assets/js/jquery.min.js"></script>

<?php require_once '../../templates/footer.php'; ?>
<script src="../../assets/js/jquery.min.js"></script>
<script src="../../assets/js/script.js"></script>
<script src="../../assets/js/onboarding_tour.js"></script>

<?php include('../../includes/semantics/footer.php'); ?>