<?php
// CHRONONAV_WEB_DOSS/pages/admin/settings.php

// Start the session at the very beginning of the script
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../middleware/auth_check.php';
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

// Ensure user is logged in and has the 'admin' role
requireRole(['admin']);

$user = $_SESSION['user'];

// --- START: Variables for Header and Sidenav ---
// These variables MUST be defined before including header_admin.php
$page_title = "Settings";
$current_page = "settings"; // For active sidebar link

// Variables for the header template (display_username, display_user_role, profile_img_src)
$display_username = htmlspecialchars($user['name'] ?? 'Admin');
$display_user_role = htmlspecialchars($user['role'] ?? 'Admin');

// Attempt to get profile image path for the header
$profile_img_src = '../../uploads/profiles/default-avatar.png'; // Default fallback
if (!empty($user['profile_img']) && file_exists('../../' . $user['profile_img'])) {
    $profile_img_src = '../../' . $user['profile_img'];
}
// --- END: Variables for Header and Sidenav ---

$message = '';
$message_type = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}
?>

<?php
// --- Include the Admin-specific Header and Sidenav ---
require_once '../../templates/admin/header_admin.php';
require_once '../../templates/admin/sidenav_admin.php';
?>

<style>
    /* CSS Styles remain unchanged for design consistency */
    body {
        font-family: "Space Grotesk", "Noto Sans", sans-serif;
        background-color: #fff;
    }

    .main-content-wrapper {
        margin-left: 20%;
        transition: margin-left 0.3s ease;
    }

    .main-dashboard-content {
        font-family: "Space Grotesk", "Noto Sans", sans-serif;
        max-width: 100%;
        padding: 0 1rem;
    }

    .settings-container {
        padding: 1rem 0;
    }

    .settings-section {
        border: none;
        border-radius: 0.75rem;
        margin-bottom: 1.5rem;
    }

    .settings-section .card-header {
        background-color: white;
        border-bottom: 1px solid #eaedf1;
        padding: 1.25rem 1.5rem;
    }

    .settings-section .card-header h5 {
        color: #101518;
        font-weight: bold;
        font-size: 1.125rem;
        margin: 0;
    }

    .settings-section .card-body {
        padding: 1.5rem;
    }

    .settings-item {
        padding: 1rem 0;
        border-bottom: 1px solid #f0f2f5;
    }

    .settings-item:last-child {
        border-bottom: none;
    }

    .settings-item span:first-child {
        color: #101518;
        font-weight: 500;
        font-size: 0.875rem;
    }

    .settings-item span:last-child {
        color: #5c748a;
        font-size: 0.875rem;
    }

    .btn-custom-outline {
        background-color: #eaedf1;
        color: #101518;
        border: none;
        border-radius: 9999px;
        font-weight: 500;
        font-size: 0.875rem;
        padding: 0.5rem 1rem;
        min-width: 84px;
    }

    .btn-custom-blue {
        background-color: #0b80ee;
        color: white;
        border: none;
        border-radius: 9999px;
        font-weight: bold;
        font-size: 0.875rem;
        padding: 0.5rem 1rem;
    }

    .btn-custom-danger {
        background-color: #dc3545;
        color: white;
        border: none;
        border-radius: 9999px;
        font-weight: 500;
        font-size: 0.875rem;
        padding: 0.5rem 1rem;
        min-width: 84px;
    }

    /* Toggle Switch Styling */
    .form-check-input:checked {
        background-color: #0b80ee;
        border-color: #0b80ee;
    }

    .form-check-input:focus {
        border-color: #0b80ee;
        box-shadow: 0 0 0 0.2rem rgba(11, 128, 238, 0.25);
    }

    .form-switch .form-check-input {
        width: 3rem;
        height: 1.5rem;
    }

    /* Modal Styling */
    .modal-header {
        border-bottom: 1px solid #eaedf1;
        padding: 1.25rem 1.5rem;
    }

    .modal-header .modal-title {
        color: #101518;
        font-weight: bold;
        font-size: 1.25rem;
    }

    .modal-body {
        padding: 1.5rem;
    }

    .modal-footer {
        border-top: 1px solid #eaedf1;
        padding: 1rem 1.5rem;
    }

    .form-label {
        color: #101518;
        font-weight: 500;
        font-size: 0.875rem;
        margin-bottom: 0.5rem;
    }

    .form-control {
        border: 1px solid #eaedf1;
        border-radius: 0.5rem;
        padding: 0.75rem;
        font-size: 0.875rem;
    }

    .form-control:focus {
        border-color: #0b80ee;
        box-shadow: 0 0 0 0.2rem rgba(11, 128, 238, 0.25);
    }

    .alert {
        border: none;
        border-radius: 0.75rem;
        padding: 1rem 1.25rem;
        margin: 1rem 0;
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

    .alert-info {
        background-color: #cff4fc;
        color: #055160;
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
    /* Dark Mode Overrides for Settings Page                                  */
    /* ====================================================================== */
    body.dark-mode {
        background-color: #121A21 !important;
        color: #E5E8EB !important;
    }

    body.dark-mode .main-content-wrapper {
        background-color: #121A21 !important;
    }

    body.dark-mode .main-dashboard-content {
        background-color: #121A21 !important;
        color: #E5E8EB !important;
    }

    /* Page title */
    body.dark-mode h2.text-dark.fw-bold.fs-3 {
        color: #E5E8EB !important;
    }

    /* Settings sections */
    body.dark-mode .settings-section.card {
        background-color: #263645 !important;
        border: 1px solid #121A21 !important;
        color: #E5E8EB !important;
    }

    body.dark-mode .settings-section .card-header {
        background-color: #121A21 !important;
        border-bottom: 1px solid #263645 !important;
    }

    body.dark-mode .settings-section .card-header h5 {
        color: #E5E8EB !important;
    }

    body.dark-mode .settings-section .card-body {
        color: #E5E8EB !important;
    }

    /* Settings items */
    body.dark-mode .settings-item {
        border-bottom: 1px solid #263645 !important;
    }

    body.dark-mode .settings-item span:first-child {
        color: #E5E8EB !important;
    }

    body.dark-mode .settings-item span:last-child {
        color: #94ADC7 !important;
    }

    /* Buttons */
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

    body.dark-mode .btn-custom-blue {
        background-color: #1C7DD6 !important;
        color: #FFFFFF !important;
        border: 1px solid #1C7DD6 !important;
    }

    body.dark-mode .btn-custom-blue:hover {
        background-color: #1a6fc0 !important;
        border-color: #1a6fc0 !important;
    }

    body.dark-mode .btn-custom-danger {
        background-color: #B71C1C !important;
        color: #FFCDD2 !important;
        border: 1px solid #C62828 !important;
    }

    body.dark-mode .btn-custom-danger:hover {
        background-color: #C62828 !important;
        color: #FFFFFF !important;
        border-color: #C62828 !important;
    }

    /* Toggle Switch Styling */
    body.dark-mode .form-check-input {
        background-color: #263645 !important;
        border-color: #121A21 !important;
    }

    body.dark-mode .form-check-input:checked {
        background-color: #1C7DD6 !important;
        border-color: #1C7DD6 !important;
    }

    body.dark-mode .form-check-input:focus {
        border-color: #1C7DD6 !important;
        box-shadow: 0 0 0 0.2rem rgba(28, 125, 214, 0.25) !important;
    }

    /* Modal Styling */
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

    body.dark-mode .modal-footer {
        background-color: #121A21 !important;
        border-top: 1px solid #263645 !important;
    }

    /* Form elements */
    body.dark-mode .form-label {
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

    body.dark-mode .form-control::placeholder {
        color: #94ADC7 !important;
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

    body.dark-mode .alert-danger {
        background-color: #B71C1C !important;
        color: #FFCDD2 !important;
        border-color: #C62828 !important;
    }

    body.dark-mode .alert-warning {
        background-color: #E65100 !important;
        color: #FFECB3 !important;
        border-color: #F57C00 !important;
    }

    body.dark-mode .alert-info {
        background-color: #0D47A1 !important;
        color: #BBDEFB !important;
        border-color: #1565C0 !important;
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

    /* Close button */
    body.dark-mode .btn-close {
        filter: invert(1) grayscale(100%) brightness(200%) !important;
    }

    /* Checkbox styling */
    body.dark-mode .form-check-input {
        background-color: #263645 !important;
        border: 1px solid #121A21 !important;
    }

    body.dark-mode .form-check-input:checked {
        background-color: #1C7DD6 !important;
        border-color: #1C7DD6 !important;
    }

    body.dark-mode .form-check-label {
        color: #E5E8EB !important;
    }

    /* Text muted in dark mode */
    body.dark-mode .text-muted {
        color: #94ADC7 !important;
    }

    /* Small text in forms */
    body.dark-mode small {
        color: #94ADC7 !important;
    }

    /* Focus states for all interactive elements */
    body.dark-mode .btn:focus,
    body.dark-mode .form-control:focus,
    body.dark-mode .form-check-input:focus {
        box-shadow: 0 0 0 0.2rem rgba(28, 125, 214, 0.25) !important;
    }

    /* Disabled button state */
    body.dark-mode .btn:disabled {
        background-color: #263645 !important;
        color: #94ADC7 !important;
        border: 1px solid #121A21 !important;
        opacity: 0.6;
    }

    /* Form validation states */
    body.dark-mode .form-control.is-valid {
        border-color: #28a745 !important;
        background-color: rgba(40, 167, 69, 0.1) !important;
    }

    body.dark-mode .form-control.is-invalid {
        border-color: #dc3545 !important;
        background-color: rgba(220, 53, 69, 0.1) !important;
    }

    body.dark-mode .valid-feedback {
        color: #81c784 !important;
    }

    body.dark-mode .invalid-feedback {
        color: #e57373 !important;
    }

    /* Password validation message */
    body.dark-mode .form-control:invalid {
        border-color: #dc3545 !important;
    }

    body.dark-mode .form-control:valid {
        border-color: #28a745 !important;
    }

    /* Toggle switch track in dark mode */
    body.dark-mode .form-switch .form-check-input {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3e%3ccircle r='3' fill='%23ffffff'/%3e%3c/svg%3e") !important;
    }

    /* Settings container background */
    body.dark-mode .settings-container {
        background-color: transparent !important;
    }

    /* Shadow adjustments for dark mode */
    body.dark-mode .shadow-sm {
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2) !important;
    }

    /* Checkbox for deactivation */
    body.dark-mode #confirm_deactivate:checked {
        background-color: #1C7DD6 !important;
        border-color: #1C7DD6 !important;
    }

    /* Modal backdrop */
    body.dark-mode .modal-backdrop {
        background-color: rgba(0, 0, 0, 0.7) !important;
    }

    /* Input group styling */
    body.dark-mode .input-group-text {
        background-color: #121A21 !important;
        border: 1px solid #263645 !important;
        color: #94ADC7 !important;
    }

    /* Card shadow in dark mode */
    body.dark-mode .card {
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2) !important;
    }

    /* Text colors for form hints */
    body.dark-mode small.text-muted {
        color: #94ADC7 !important;
    }

    /* Button group styling */
    body.dark-mode .btn-group .btn {
        border-color: #121A21 !important;
    }

    /* Table styling (if any tables in settings) */
    body.dark-mode .table {
        color: #E5E8EB !important;
    }

    body.dark-mode .table th {
        background-color: #121A21 !important;
        color: #E5E8EB !important;
        border-color: #263645 !important;
    }

    body.dark-mode .table td {
        color: #94ADC7 !important;
        border-color: #263645 !important;
    }

    /* Dropdowns (if any) */
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

    /* Popovers */
    body.dark-mode .popover {
        background-color: #263645 !important;
        border: 1px solid #121A21 !important;
        color: #E5E8EB !important;
    }

    body.dark-mode .popover-header {
        background-color: #121A21 !important;
        border-bottom: 1px solid #263645 !important;
        color: #E5E8EB !important;
    }

    /* Progress bars (if any) */
    body.dark-mode .progress {
        background-color: #121A21 !important;
    }

    body.dark-mode .progress-bar {
        background-color: #1C7DD6 !important;
    }

    /* List groups (if any) */
    body.dark-mode .list-group-item {
        background-color: #263645 !important;
        border-color: #121A21 !important;
        color: #E5E8EB !important;
    }

    body.dark-mode .list-group-item.active {
        background-color: #1C7DD6 !important;
        border-color: #1C7DD6 !important;
        color: #FFFFFF !important;
    }

    /* Container spacing */
    body.dark-mode .container,
    body.dark-mode .container-fluid {
        background-color: transparent !important;
    }
</style>



<div class="main-content-wrapper" style="margin-left: 20%;">
    <div class="main-dashboard-content">

        <div class="d-flex flex-wrap justify-content-between gap-3 p-3">
            <h2 class="text-dark fw-bold fs-3 mb-0" style="min-width: 288px;"><?= $page_title ?></h2>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?> alert-dismissible fade show m-3" role="alert">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="settings-container">
            
            <div class="settings-section card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Display</h5>
                </div>
                <div class="card-body">
                    <div class="settings-item d-flex justify-content-between align-items-center">
                        <span>Font Size</span>
                        <span>Medium</span>
                    </div>
                    <div class="settings-item d-flex justify-content-between align-items-center">
                        <span>Dark Mode</span>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="darkModeSwitch">
                        </div>
                    </div>
                </div>
            </div>


            <div class="settings-section card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Language</h5>
                </div>
                <div class="card-body">
                    <div class="settings-item d-flex justify-content-between align-items-center">
                        <span>Language</span>
                        <span>English</span>
                    </div>
                </div>
            </div>


            <div class="settings-section card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Account Management</h5>
                </div>
                <div class="card-body">
                    <div class="settings-item d-flex justify-content-between align-items-center">
                        <span>Change Password</span>
                        <button type="button" class="btn btn-custom-outline btn-sm" data-bs-toggle="modal"
                            data-bs-target="#changePasswordModal">Change Password</button>
                    </div>
                    <div class="settings-item d-flex justify-content-between align-items-center">
                        <span>Deactivate Account</span>
                        <button type="button" class="btn btn-custom-danger btn-sm" data-bs-toggle="modal"
                            data-bs-target="#deactivateAccountModal">Deactivate Account</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="../../includes/admin_change_password_handler.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="changePasswordModalLabel">Change Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password"
                            required>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                        <small class="text-muted">Password must be at least 8 characters, include an uppercase letter, a
                            number, and a symbol.</small>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                            required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-custom-outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-custom-blue">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>


<div class="modal fade" id="deactivateAccountModal" tabindex="-1" aria-labelledby="deactivateAccountModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="../../includes/admin_deactivate_handler.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="deactivateAccountModalLabel">Deactivate Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- START: MODAL BODY CONTENT -->
                <div class="modal-body">
                    <p class="text-muted mb-3">Are you sure you want to deactivate your account? This action is
                        permanent and cannot be undone.</p>

                    <!-- Input for Current Password (Required Security Check) -->
                    <div class="mb-3">
                        <label for="deactivate_password" class="form-label">Enter Current Password to Confirm</label>
                        <input type="password" class="form-control" id="deactivate_password" name="current_password"
                            required>
                    </div>

                    <!-- Confirmation Checkbox -->
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="confirm_deactivate"
                            name="confirm_deactivate">
                        <label class="form-check-label" for="confirm_deactivate">I understand and want to proceed with
                            deactivating my account.</label>
                    </div>
                </div>
                <!-- END: MODAL BODY CONTENT -->

                <div class="modal-footer">
                    <button type="button" class="btn btn-custom-outline" data-bs-dismiss="modal">Cancel</button>
                    <!-- Button starts disabled, enabled by JS on checking the box AND typing the password -->
                    <button type="submit" class="btn btn-custom-danger" id="deactivateSubmitButton" disabled>Deactivate
                        Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>

<script>
    $('#voiceGuidance, #contrastMode').change(function () {
        let setting = $(this).attr('id');
        let value = $(this).is(':checked') ? 1 : 0;
        console.log(setting + ' changed to ' + value);
    });

    // --- Deactivation Button Logic FIX ---
    document.addEventListener('DOMContentLoaded', function () {
        const confirmCheckbox = document.getElementById('confirm_deactivate');
        const deactivateButton = document.getElementById('deactivateSubmitButton');
        const deactivatePasswordInput = document.getElementById('deactivate_password');

        // Function to update the button state
        function updateDeactivateButtonState() {
            if (deactivateButton && confirmCheckbox && deactivatePasswordInput) {
                const passwordEntered = deactivatePasswordInput.value.trim() !== '';

                // Enable button only if BOTH conditions are met
                deactivateButton.disabled = !(confirmCheckbox.checked && passwordEntered);
            }
        }

        if (confirmCheckbox && deactivateButton && deactivatePasswordInput) {
            // 1. Initial State: Set to disabled
            updateDeactivateButtonState();

            // 2. Event Listeners: Re-check state whenever EITHER input changes
            confirmCheckbox.addEventListener('change', updateDeactivateButtonState);
            deactivatePasswordInput.addEventListener('input', updateDeactivateButtonState);
            deactivatePasswordInput.addEventListener('keyup', updateDeactivateButtonState); // Added keyup for robustness

            // 3. Reset State on Modal Open 
            const deactivateModal = document.getElementById('deactivateAccountModal');
            if (deactivateModal) {
                deactivateModal.addEventListener('show.bs.modal', function () {
                    // Force reset checkbox and password field when modal opens
                    confirmCheckbox.checked = false;
                    deactivatePasswordInput.value = '';
                    deactivateButton.disabled = true;
                });
            }
        }

        // --- Password Validation (Client-Side Check for Change Password Modal) ---
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');

        if (newPassword && confirmPassword) {
            const checkPasswordMatch = () => {
                if (newPassword.value !== confirmPassword.value) {
                    // Set custom message when passwords don't match
                    confirmPassword.setCustomValidity("Passwords do not match.");
                } else {
                    // Clear custom message when they match
                    confirmPassword.setCustomValidity("");
                }
            };
            newPassword.addEventListener('input', checkPasswordMatch);
            confirmPassword.addEventListener('input', checkPasswordMatch);
        }
    });
</script>