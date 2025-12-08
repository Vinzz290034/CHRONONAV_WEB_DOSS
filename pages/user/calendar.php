<?php
// CHRONONAV_WEB_UNO/pages/user/calendar.php
require_once '../../middleware/auth_check.php';
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

// Ensure only users can access this page
requireRole(['user']);
/** @var \mysqli $conn */

$user = $_SESSION['user'];
$current_user_id = $user['id'];

// --- Fetch fresh user data for display in header and profile sections ---
$stmt_user_data = $conn->prepare("SELECT name, email, profile_img, role FROM users WHERE id = ?");
if ($stmt_user_data) {
    $stmt_user_data->bind_param("i", $current_user_id);
    $stmt_user_data->execute();
    $result_user_data = $stmt_user_data->get_result();
    if ($result_user_data->num_rows > 0) {
        $user_from_db = $result_user_data->fetch_assoc();
        $_SESSION['user'] = array_merge($_SESSION['user'], $user_from_db);
        $user = $_SESSION['user'];
    } else {
        error_log("Security Alert: User ID {$current_user_id} in session not found in database for calendar (user).");
        session_destroy();
        header('Location: ../../auth/login.php?error=user_not_found');
        exit();
    }
    $stmt_user_data->close();
} else {
    error_log("Database query preparation failed for calendar (user): " . $conn->error);
}

// Prepare variables for header display
$display_username = htmlspecialchars($user['name'] ?? 'Guest');
$display_user_role = htmlspecialchars(ucfirst($user['role'] ?? 'User'));

// Determine the correct profile image source path for the header
$display_profile_img = htmlspecialchars($user['profile_img'] ?? 'uploads/profiles/default-avatar.png');
$profile_img_src = (strpos($display_profile_img, 'uploads/') === 0) ? '../../' . $display_profile_img : $display_profile_img;

$page_title = "My Academic Calendar";
$current_page = "schedule";

$message = '';
$message_type = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// --- Handle Actions: Delete User's Personal Event or Unsave Admin Event ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_event'])) {
    $user_event_id = $_POST['user_event_id'] ?? null;

    if (empty($user_event_id)) {
        $_SESSION['message'] = "Event ID is required to delete.";
        $_SESSION['message_type'] = 'danger';
    } else {
        // Delete from user_calendar_events table, ensuring the user owns it
        $stmt = $conn->prepare("DELETE FROM user_calendar_events WHERE id = ? AND user_id = ?");
        if ($stmt) {
            $stmt->bind_param("ii", $user_event_id, $current_user_id);
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $_SESSION['message'] = "Event removed from your calendar successfully!";
                    $_SESSION['message_type'] = 'success';
                } else {
                    $_SESSION['message'] = "Error removing event: Event not found or you don't have permission.";
                    $_SESSION['message_type'] = 'warning';
                }
            } else {
                $_SESSION['message'] = "Error removing event: " . $stmt->error;
                $_SESSION['message_type'] = 'danger';
            }
            $stmt->close();
        } else {
            $_SESSION['message'] = "Database error preparing event removal: " . $conn->error;
            $_SESSION['message_type'] = 'danger';
        }
    }
    header("Location: calendar.php");
    exit();
}

// --- Fetch User's Personal and Saved Admin Calendar Events ---
$events = [];

// 1. Fetch public events from the main calendar_events table that are *not* already saved by the user
$stmt_public_events = $conn->prepare("
    SELECT ce.id, ce.event_name, ce.description, ce.start_date, ce.end_date, ce.location, ce.event_type, 'public' as source_type, u.name AS posted_by_name
    FROM calendar_events ce
    LEFT JOIN user_calendar_events uce ON ce.id = uce.calendar_event_id AND uce.user_id = ?
    LEFT JOIN users u ON ce.user_id = u.id
    WHERE uce.id IS NULL
    ORDER BY ce.start_date ASC
");
if ($stmt_public_events) {
    $stmt_public_events->bind_param("i", $current_user_id);
    $stmt_public_events->execute();
    $result_public_events = $stmt_public_events->get_result();
    while ($row = $result_public_events->fetch_assoc()) {
        $events[] = $row;
    }
    $stmt_public_events->close();
} else {
    error_log("Database error fetching public events: " . $conn->error);
}

// 2. Fetch events from the user_calendar_events table (personal events and saved admin events)
$stmt_user_events = $conn->prepare("
    SELECT
        uce.id,
        uce.event_name,
        uce.description,
        uce.start_date,
        uce.end_date,
        uce.location,
        uce.event_type,
        'personal' as source_type,
        IFNULL(u_orig.name, u_creator.name) AS posted_by_name,
        CASE WHEN uce.calendar_event_id IS NULL THEN TRUE ELSE FALSE END AS is_personal_event
    FROM user_calendar_events uce
    LEFT JOIN calendar_events ce_orig ON uce.calendar_event_id = ce_orig.id
    LEFT JOIN users u_orig ON ce_orig.user_id = u_orig.id
    LEFT JOIN users u_creator ON uce.user_id = u_creator.id
    WHERE uce.user_id = ?
    ORDER BY uce.start_date ASC
");
if ($stmt_user_events) {
    $stmt_user_events->bind_param("i", $current_user_id);
    $stmt_user_events->execute();
    $result_user_events = $stmt_user_events->get_result();
    while ($row = $result_user_events->fetch_assoc()) {
        $events[] = $row;
    }
    $stmt_user_events->close();
} else {
    error_log("Database error fetching user's personal events: " . $conn->error);
}

// Sort all events by date after combining
usort($events, function ($a, $b) {
    return strtotime($a['start_date']) - strtotime($b['start_date']);
});

// Group events by month/year for display
$grouped_events = [];
foreach ($events as $event) {
    $month_year = date('F Y', strtotime($event['start_date']));
    if (!isset($grouped_events[$month_year])) {
        $grouped_events[$month_year] = [];
    }
    $grouped_events[$month_year][] = $event;
    // Keep events within each month sorted by start_date
    usort($grouped_events[$month_year], function ($a, $b) {
        return strtotime($a['start_date']) - strtotime($b['start_date']);
    });
}
// Sort months chronologically
uksort($grouped_events, function ($a, $b) {
    return strtotime($a . ' 1') - strtotime($b . ' 1');
});

$event_types = ['Personal', 'Other', 'Study Group', 'Appointment'];
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
        background-color: rgb(255, 255, 255);
        font-family: "Space Grotesk", "Noto Sans", sans-serif;
    }

    .main-dashboard-content {
        margin-left: 20%;
        padding: 20px 35px;
        min-height: 100vh;
        font-family: "Space Grotesk", "Noto Sans", sans-serif;
    }

    /* Header styling to match dashboard */
    .dashboard-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 0;
        margin-bottom: 20px;
    }

    .dashboard-header h2 {
        font-size: 28px;
        font-weight: bold;
        color: #101518;
        margin: 0;
    }

    /* Button styling to match dashboard */
    .btn-custom-primary {
        background-color: #dce8f3;
        color: #101518;
        border: none;
        border-radius: 9999px;
        font-weight: 500;
        font-size: 0.875rem;
        padding: 8px 20px;
        height: 40px;
        transition: all 0.3s ease;
    }

    .btn-custom-primary:hover {
        background-color: #c5d8eb;
        color: #101518;
    }

    /* Calendar month card styling */
    .calendar-month-card {
        border-radius: 0.75rem;
        overflow: hidden;
        border: none;
        background: white;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        margin-bottom: 20px;
    }

    .calendar-month-card .card-header {
        background-color: #eaedf1;
        border-bottom: none;
        padding: 16px 24px;
    }

    .calendar-month-card .card-header h5 {
        font-size: 18px;
        font-weight: 600;
        color: #101518;
        margin: 0;
    }

    /* Event item styling */
    .calendar-event-item {
        padding: 20px 24px;
        border-bottom: 1px solid #f0f0f0;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        transition: background-color 0.3s ease;
    }

    .calendar-event-item:last-child {
        border-bottom: none;
    }

    .calendar-event-item:hover {
        background-color: #f9fafb;
    }

    .event-details {
        flex: 1;
        margin-right: 20px;
    }

    .event-details h6 {
        font-size: 16px;
        font-weight: 600;
        color: #101518;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .event-type-badge {
        font-size: 0.75rem;
        font-weight: 500;
        padding: 4px 10px;
        border-radius: 9999px;
    }

    .event-type-badge.Personal {
        background-color: #dce8f3;
        color: #2e78c6;
    }

    .event-type-badge.Other {
        background-color: #e8d8f3;
        color: #7c3aed;
    }

    .event-type-badge.Study {
        background-color: #d8f3e8;
        color: #059669;
    }

    .event-type-badge.Appointment {
        background-color: #f3e8d8;
        color: #d97706;
    }

    .event-details p {
        font-size: 14px;
        color: #5c748a;
        margin-bottom: 6px;
    }

    .event-details p i {
        width: 16px;
        margin-right: 8px;
    }

    .posted-by-info {
        font-size: 13px;
        color: #737373;
        margin-top: 8px;
        font-style: italic;
    }

    /* Event actions styling */
    .event-actions {
        display: flex;
        gap: 10px;
        flex-shrink: 0;
    }

    .event-actions .btn-sm {
        padding: 6px 12px;
        font-size: 0.75rem;
        border-radius: 9999px;
        font-weight: 500;
        border: none;
    }

    .event-actions .btn-danger {
        background-color: #fee2e2;
        color: #dc2626;
    }

    .event-actions .btn-danger:hover {
        background-color: #fecaca;
    }

    .event-actions .btn-warning {
        background-color: #fef3c7;
        color: #d97706;
    }

    .event-actions .btn-warning:hover {
        background-color: #fde68a;
    }

    .event-actions .btn-success {
        background-color: #d1fae5;
        color: #059669;
    }

    .event-actions .btn-success:hover {
        background-color: #a7f3d0;
    }

    /* Modal styling */
    .modal-content {
        border-radius: 0.75rem;
        border: none;
    }

    .modal-header {
        background-color: #eaedf1;
        border-bottom: 1px solid #e5e7eb;
        padding: 20px 24px;
    }

    .modal-title {
        font-size: 18px;
        font-weight: 600;
        color: #101518;
    }

    .modal-body {
        padding: 24px;
    }

    .form-label {
        font-size: 14px;
        font-weight: 500;
        color: #374151;
        margin-bottom: 6px;
    }

    .form-control,
    .form-select {
        border-radius: 0.5rem;
        border: 1px solid #d1d5db;
        padding: 10px 12px;
        font-size: 14px;
        color: #101518;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #2e78c6;
        box-shadow: 0 0 0 3px rgba(46, 120, 198, 0.1);
    }

    .modal-footer {
        border-top: 1px solid #e5e7eb;
        padding: 16px 24px;
    }

    .modal-footer .btn-secondary {
        background-color: #eaedf1;
        color: #101518;
        border: none;
        border-radius: 9999px;
        padding: 8px 20px;
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

    /* Scrollbar styling to match dashboard */
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
        .main-dashboard-content {
            margin-left: 0;
            padding: 15px;
            font-family: "Space Grotesk", "Noto Sans", sans-serif;
        }

        .dashboard-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 15px;
            padding: 15px 0;
        }

        .dashboard-header h2 {
            font-size: 22px;
        }

        .calendar-event-item {
            flex-direction: column;
            padding: 16px;
        }

        .event-details {
            margin-right: 0;
            margin-bottom: 15px;
        }

        .event-actions {
            width: 100%;
            justify-content: flex-start;
        }

        .calendar-month-card .card-header {
            padding: 12px 16px;
        }
    }

    @media (min-width: 768px) and (max-width: 1023px) {
        .main-dashboard-content {
            margin-left: 80px;
            padding: 20px 25px;
            font-family: "Space Grotesk", "Noto Sans", sans-serif;
        }

        .dashboard-header h2 {
            font-size: 24px;
        }
    }

    @media (min-width: 1024px) {
        .main-dashboard-content {
            margin-left: 20%;
            padding: 20px 35px;
            font-family: "Space Grotesk", "Noto Sans", sans-serif;
        }
    }



    /* ====================================================================== */
    /* Dark Mode Overrides for Calendar Page - Custom Colors                 */
    /* ====================================================================== */
    body.dark-mode {
        background-color: #121A21 !important;
        /* Primary dark background */
        color: #E5E8EB !important;
    }

    /* Main content containers */
    body.dark-mode .main-dashboard-content {
        background-color: #121A21 !important;
        color: #E5E8EB !important;
        font-family: "Space Grotesk", "Noto Sans", sans-serif;
    }

    /* Header and titles */
    body.dark-mode .dashboard-header h2 {
        color: #E5E8EB !important;
        /* Light text for page title */
    }

    /* Buttons */
    body.dark-mode .btn-custom-primary {
        background-color: #263645 !important;
        /* Secondary dark */
        color: #94ADC7 !important;
        /* Secondary text */
        border: 1px solid #121A21 !important;
        /* Primary border */
    }

    body.dark-mode .btn-custom-primary:hover {
        background-color: #1C7DD6 !important;
        /* Active blue on hover */
        color: #FFFFFF !important;
        /* White text on hover */
        border-color: #1C7DD6 !important;
    }

    /* Calendar month cards */
    body.dark-mode .calendar-month-card {
        background-color: #263645 !important;
        /* Secondary dark background */
        border: 1px solid #121A21 !important;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3) !important;
    }

    body.dark-mode .calendar-month-card .card-header {
        background-color: #121A21 !important;
        /* Primary dark */
        border-bottom: 1px solid #263645 !important;
        /* Secondary border */
    }

    body.dark-mode .calendar-month-card .card-header h5 {
        color: #E5E8EB !important;
        /* Light text for month headers */
    }

    /* Event items */
    body.dark-mode .calendar-event-item {
        background-color: #121A21 !important;
        /* Primary dark background */
        border-bottom: 1px solid #263645 !important;
        /* Secondary border */
        color: #E5E8EB !important;
    }

    body.dark-mode .calendar-event-item:hover {
        background-color: #263645 !important;
        /* Secondary dark on hover */
    }

    /* Event details */
    body.dark-mode .event-details h6 {
        color: #E5E8EB !important;
        /* Light text for event titles */
    }

    body.dark-mode .event-details p.text-muted {
        color: #94ADC7 !important;
        /* Secondary text for dates/times */
    }

    body.dark-mode .event-details p {
        color: #E5E8EB !important;
        /* Light text for descriptions */
    }

    body.dark-mode .posted-by-info {
        color: #94ADC7 !important;
        /* Secondary text for posted by info */
    }

    /* Event type badges */
    body.dark-mode .event-type-badge {
        background-color: #121A21 !important;
        /* Primary dark */
        border: 1px solid #263645 !important;
    }

    body.dark-mode .event-type-badge.Personal {
        background-color: #0D47A1 !important;
        /* Dark blue */
        color: #BBDEFB !important;
        /* Light blue text */
        border-color: #1565C0 !important;
    }

    body.dark-mode .event-type-badge.Other {
        background-color: #4A148C !important;
        /* Dark purple */
        color: #E1BEE7 !important;
        /* Light purple text */
        border-color: #6A1B9A !important;
    }

    body.dark-mode .event-type-badge.Study {
        background-color: #1B5E20 !important;
        /* Dark green */
        color: #C8E6C9 !important;
        /* Light green text */
        border-color: #2E7D32 !important;
    }

    body.dark-mode .event-type-badge.Appointment {
        background-color: #E65100 !important;
        /* Dark orange */
        color: #FFE0B2 !important;
        /* Light orange text */
        border-color: #EF6C00 !important;
    }

    /* Action buttons */
    body.dark-mode .event-actions .btn-danger {
        background-color: #121A21 !important;
        /* Primary dark */
        color: #E57373 !important;
        /* Light red */
        border: 1px solid #263645 !important;
    }

    body.dark-mode .event-actions .btn-danger:hover {
        background-color: #C62828 !important;
        /* Dark red on hover */
        color: #FFFFFF !important;
        border-color: #C62828 !important;
    }

    body.dark-mode .event-actions .btn-warning {
        background-color: #121A21 !important;
        /* Primary dark */
        color: #FFB74D !important;
        /* Light orange */
        border: 1px solid #263645 !important;
    }

    body.dark-mode .event-actions .btn-warning:hover {
        background-color: #F57C00 !important;
        /* Dark orange on hover */
        color: #FFFFFF !important;
        border-color: #F57C00 !important;
    }

    body.dark-mode .event-actions .btn-success {
        background-color: #121A21 !important;
        /* Primary dark */
        color: #81C784 !important;
        /* Light green */
        border: 1px solid #263645 !important;
    }

    body.dark-mode .event-actions .btn-success:hover {
        background-color: #1B5E20 !important;
        /* Dark green on hover */
        color: #FFFFFF !important;
        border-color: #1B5E20 !important;
    }

    /* Modals */
    body.dark-mode .modal-content {
        background-color: #263645 !important;
        /* Secondary dark background */
        border: 1px solid #121A21 !important;
        color: #E5E8EB !important;
    }

    body.dark-mode .modal-header {
        background-color: #121A21 !important;
        /* Primary dark */
        border-bottom: 1px solid #263645 !important;
    }

    body.dark-mode .modal-title {
        color: #E5E8EB !important;
        /* Light text for modal titles */
    }

    body.dark-mode .modal-footer {
        background-color: #121A21 !important;
        /* Primary dark */
        border-top: 1px solid #263645 !important;
    }

    /* Form elements */
    body.dark-mode .form-control,
    body.dark-mode .form-select {
        background-color: #121A21 !important;
        /* Primary dark */
        border: 1px solid #263645 !important;
        /* Secondary border */
        color: #E5E8EB !important;
        /* Light text */
    }

    body.dark-mode .form-control:focus,
    body.dark-mode .form-select:focus {
        background-color: #121A21 !important;
        border-color: #1C7DD6 !important;
        /* Blue focus */
        color: #E5E8EB !important;
        box-shadow: 0 0 0 3px rgba(28, 125, 214, 0.2) !important;
    }

    body.dark-mode .form-label {
        color: #94ADC7 !important;
        /* Secondary text for labels */
    }

    /* Secondary button in modals */
    body.dark-mode .modal-footer .btn-secondary {
        background-color: #121A21 !important;
        /* Primary dark */
        color: #94ADC7 !important;
        /* Secondary text */
        border: 1px solid #263645 !important;
    }

    body.dark-mode .modal-footer .btn-secondary:hover {
        background-color: #263645 !important;
        /* Secondary dark on hover */
        color: #E5E8EB !important;
        /* Light text on hover */
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

    body.dark-mode .alert-warning {
        background-color: #F57C00 !important;
        /* Dark orange */
        color: #FFE0B2 !important;
        /* Light orange text */
        border-color: #EF6C00 !important;
    }

    body.dark-mode .alert-danger {
        background-color: #B71C1C !important;
        /* Dark red */
        color: #FFCDD2 !important;
        /* Light red text */
        border-color: #C62828 !important;
    }

    /* Icons */
    body.dark-mode .fa-plus,
    body.dark-mode .fa-trash-alt,
    body.dark-mode .fa-edit,
    body.dark-mode .fa-calendar-alt,
    body.dark-mode .fa-map-marker-alt,
    body.dark-mode .fa-user-tie {
        color: #94ADC7 !important;
        /* Secondary color for icons */
    }

    body.dark-mode .btn:hover .fa-plus,
    body.dark-mode .btn:hover .fa-trash-alt,
    body.dark-mode .btn:hover .fa-edit {
        color: #FFFFFF !important;
        /* White icons on hover */
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

    /* Close button in modals */
    body.dark-mode .btn-close {
        filter: invert(1) grayscale(100%) brightness(200%) !important;
    }

    /* List group items */
    body.dark-mode .list-group-item {
        background-color: #121A21 !important;
        /* Primary dark */
        border-color: #263645 !important;
        /* Secondary border */
        color: #E5E8EB !important;
    }

    /* Responsive adjustments for dark mode */
    @media (max-width: 767px) {
        body.dark-mode .main-dashboard-content {
            background-color: #121A21 !important;
            font-family: "Space Grotesk", "Noto Sans", sans-serif;
        }

        body.dark-mode .calendar-event-item {
            background-color: #121A21 !important;
        }

        body.dark-mode .calendar-month-card {
            background-color: #263645 !important;
        }
    }

    @media (min-width: 768px) and (max-width: 1023px) {
        body.dark-mode .main-dashboard-content {
            background-color: #121A21 !important;
            font-family: "Space Grotesk", "Noto Sans", sans-serif;
        }
    }

    @media (min-width: 1024px) {
        body.dark-mode .main-dashboard-content {
            background-color: #121A21 !important;
            font-family: "Space Grotesk", "Noto Sans", sans-serif;
        }
    }

    /* Empty state */
    body.dark-mode .alert-info:empty {
        background-color: #0D47A1 !important;
        color: #BBDEFB !important;
    }

    /* Focus states for accessibility */
    body.dark-mode .btn:focus,
    body.dark-mode .form-control:focus,
    body.dark-mode .form-select:focus,
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

    /* Border utilities */
    body.dark-mode .border-0 {
        border-color: #263645 !important;
        /* Secondary border instead of removing */
    }

    /* Card shadows */
    body.dark-mode .calendar-month-card .shadow-sm {
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3) !important;
    }

    /* Hover effects for event cards */
    body.dark-mode .calendar-month-card:hover {
        border-color: #1C7DD6 !important;
        transition: border-color 0.3s ease;
    }

    /* Button text colors */
    body.dark-mode .btn-sm {
        font-weight: 500;
    }

    /* Text emphasis */
    body.dark-mode strong {
        color: #E5E8EB !important;
        /* Light text for strong elements */
    }
</style>

<div class="main-dashboard-content">
    <div class="dashboard-header">
        <h2><?= $page_title ?></h2>
        <button type="button" class="btn btn-custom-primary" data-bs-toggle="modal" data-bs-target="#addEventModal">
            <i class="fas fa-plus me-1"></i> Add My Event
        </button>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="calendar-events-list">
        <?php if (empty($grouped_events)): ?>
            <div class="alert alert-info">You have no academic events scheduled yet.</div>
        <?php else: ?>
            <?php foreach ($grouped_events as $month_year => $month_events): ?>
                <div class="card mb-4 calendar-month-card">
                    <div class="card-header">
                        <h5 class="mb-0"><?= $month_year ?></h5>
                    </div>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($month_events as $event): ?>
                            <li class="list-group-item calendar-event-item">
                                <div class="event-details">
                                    <h6><?= htmlspecialchars($event['event_name']) ?> <span
                                            class="badge event-type-badge <?= htmlspecialchars($event['event_type']) ?>"><?= htmlspecialchars($event['event_type']) ?></span>
                                    </h6>
                                    <p class="mb-1 text-muted">
                                        <i class="far fa-calendar-alt"></i>
                                        <?= date('M d, Y, h:i A', strtotime($event['start_date'])) ?>
                                        <?php if (date('Y-m-d', strtotime($event['start_date'])) !== date('Y-m-d', strtotime($event['end_date']))): ?>
                                            - <?= date('M d, Y, h:i A', strtotime($event['end_date'])) ?>
                                        <?php endif; ?>
                                    </p>
                                    <?php if (!empty($event['description'])): ?>
                                        <p class="mb-0"><?= nl2br(htmlspecialchars($event['description'])) ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($event['location'])): ?>
                                        <p class="mb-0"><i class="fas fa-map-marker-alt"></i>
                                            <?= htmlspecialchars($event['location']) ?></p>
                                    <?php endif; ?>

                                    <?php if (isset($event['posted_by_name']) && $event['source_type'] === 'public'): ?>
                                        <p class="posted-by-info"><i class="fas fa-user-tie"></i> Posted By:
                                            <?= htmlspecialchars($event['posted_by_name']) ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <div class="event-actions">
                                    <?php if ($event['source_type'] === 'personal'): ?>
                                        <form action="calendar.php" method="POST" class="d-inline"
                                            onsubmit="return confirm('Are you sure you want to remove this event from your calendar?');">
                                            <input type="hidden" name="user_event_id" value="<?= htmlspecialchars($event['id']) ?>">
                                            <button type="submit" name="delete_event" class="btn btn-sm btn-danger"><i
                                                    class="fas fa-trash-alt"></i> Remove</button>
                                        </form>
                                        <?php if (isset($event['is_personal_event']) && $event['is_personal_event']): ?>
                                            <button type="button" class="btn btn-sm btn-warning edit-event-btn" data-bs-toggle="modal"
                                                data-bs-target="#editEventModal" data-event-id="<?= htmlspecialchars($event['id']) ?>"
                                                data-event-name="<?= htmlspecialchars($event['event_name']) ?>"
                                                data-event-description="<?= htmlspecialchars($event['description']) ?>"
                                                data-start-date="<?= htmlspecialchars(date('Y-m-d\TH:i', strtotime($event['start_date']))) ?>"
                                                data-end-date="<?= htmlspecialchars(date('Y-m-d\TH:i', strtotime($event['end_date']))) ?>"
                                                data-event-location="<?= htmlspecialchars($event['location']) ?>"
                                                data-event-type="<?= htmlspecialchars($event['event_type']) ?>">
                                                <i class="fas fa-edit"></i> Edit My Event
                                            </button>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <form action="save_admin_event.php" method="POST" class="d-inline">
                                            <input type="hidden" name="calendar_event_id" value="<?= htmlspecialchars($event['id']) ?>">
                                            <button type="submit" name="save_event" class="btn btn-sm btn-success"><i
                                                    class="fas fa-plus"></i> Add to My Calendar</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="addEventModal" tabindex="-1" aria-labelledby="addEventModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="../../includes/add_user_event_handlers.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="addEventModalLabel">Add New Personal Event</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="event_name" class="form-label">Event Name</label>
                        <input type="text" class="form-control" id="event_name" name="event_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="event_description" class="form-label">Description (Optional)</label>
                        <textarea class="form-control" id="event_description" name="event_description"
                            rows="3"></textarea>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="start_date" class="form-label">Start Date & Time</label>
                            <input type="datetime-local" class="form-control" id="start_date" name="start_date"
                                required>
                        </div>
                        <div class="col-md-6">
                            <label for="end_date" class="form-label">End Date & Time</label>
                            <input type="datetime-local" class="form-control" id="end_date" name="end_date">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="event_location" class="form-label">Location (Optional)</label>
                        <input type="text" class="form-control" id="event_location" name="event_location">
                    </div>
                    <div class="mb-3">
                        <label for="event_type" class="form-label">Event Type</label>
                        <select class="form-select" id="event_type" name="event_type" required>
                            <?php foreach ($event_types as $type): ?>
                                <option value="<?= htmlspecialchars($type) ?>"><?= htmlspecialchars($type) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-custom-primary">Save Event</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editEventModal" tabindex="-1" aria-labelledby="editEventModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="../../includes/edit_user_event_handlers.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="editEventModalLabel">Edit My Personal Event</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="event_id" id="edit_event_id">
                    <div class="mb-3">
                        <label for="edit_event_name" class="form-label">Event Name</label>
                        <input type="text" class="form-control" id="edit_event_name" name="event_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_event_description" class="form-label">Description (Optional)</label>
                        <textarea class="form-control" id="edit_event_description" name="event_description"
                            rows="3"></textarea>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_start_date" class="form-label">Start Date & Time</label>
                            <input type="datetime-local" class="form-control" id="edit_start_date" name="start_date"
                                required>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_end_date" class="form-label">End Date & Time</label>
                            <input type="datetime-local" class="form-control" id="edit_end_date" name="end_date">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_event_location" class="form-label">Location (Optional)</label>
                        <input type="text" class="form-control" id="edit_event_location" name="event_location">
                    </div>
                    <div class="mb-3">
                        <label for="edit_event_type" class="form-label">Event Type</label>
                        <select class="form-select" id="edit_event_type" name="event_type" required>
                            <?php foreach ($event_types as $type): ?>
                                <option value="<?= htmlspecialchars($type) ?>"><?= htmlspecialchars($type) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-custom-primary">Update Event</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Event listener for populating the edit modal with data attributes
        const editEventModal = document.getElementById('editEventModal');
        if (editEventModal) {
            editEventModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const eventId = button.getAttribute('data-event-id');
                const eventName = button.getAttribute('data-event-name');
                const eventDescription = button.getAttribute('data-event-description');
                const startDate = button.getAttribute('data-start-date');
                const endDate = button.getAttribute('data-end-date');
                const eventLocation = button.getAttribute('data-event-location');
                const eventType = button.getAttribute('data-event-type');

                // Populate the form fields
                const form = editEventModal.querySelector('form');
                form.querySelector('#edit_event_id').value = eventId;
                form.querySelector('#edit_event_name').value = eventName;
                form.querySelector('#edit_event_description').value = eventDescription;
                form.querySelector('#edit_start_date').value = startDate;
                form.querySelector('#edit_end_date').value = endDate;
                form.querySelector('#edit_event_location').value = eventLocation;
                form.querySelector('#edit_event_type').value = eventType;
            });
        }
    });
</script>


<!-- Bootstrap JS Bundle with Popper -->

<?php include_once '../../templates/footer.php'; ?>
<script src="../../assets/js/jquery.min.js"></script>
<script src="../../assets/js/script.js"></script>
<script src="../../assets/js/onboarding_tour.js"></script>

<?php
// Ensure this is the last include in your main page file
require_once '../../templates/footer.php';
?>