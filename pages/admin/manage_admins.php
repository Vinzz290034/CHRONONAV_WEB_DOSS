<?php
// CHRONONAV_WEB_DOSS/pages/admin/manage_admins.php
// Admin user management page - allows admins to create/manage other admin accounts

require_once '../../config/db_connect.php';
require_once '../../middleware/auth_check.php';
/** @var \mysqli $conn */ // FIX: Resolves "Undefined method/property" IntelliSense errors on $conn.

// Check if current user is an admin
if ($_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit();
}

$page_title = "Admin Management";
$current_page = "manage_admins";
$message = "";
$message_type = "info";

// Handle form submission for creating new admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'create_admin') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';

        // Validation
        $errors = [];

        if (empty($name)) {
            $errors[] = "Name is required.";
        }
        if (empty($email)) {
            $errors[] = "Email is required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format.";
        }
        if (empty($password)) {
            $errors[] = "Password is required.";
        } elseif (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters.";
        }
        if ($password !== $password_confirm) {
            $errors[] = "Passwords do not match.";
        }

        if (empty($errors)) {
            // Check if email already exists
            $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $check_stmt->bind_param("s", $email);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows > 0) {
                $errors[] = "Email already exists in the system.";
            }
            $check_stmt->close();
        }

        if (empty($errors)) {
            // Create the admin account
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = 'admin';

            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("ssss", $name, $email, $hashed_password, $role);

            if ($stmt->execute()) {
                $message = "Admin account created successfully! Email: " . htmlspecialchars($email);
                $message_type = "success";
                // Clear form fields
                $name = $email = $password = $password_confirm = "";
            } else {
                $message = "Error creating admin account: " . $stmt->error;
                $message_type = "danger";
            }
            $stmt->close();
        } else {
            $message = "Error: " . implode(" ", $errors);
            $message_type = "danger";
        }
    }
}

// Fetch all admin accounts
$admin_accounts = [];
$result = $conn->query("SELECT id, name, email, created_at FROM users WHERE role = 'admin' ORDER BY created_at DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $admin_accounts[] = $row;
    }
}

// Include header and sidenav
require_once '../../templates/admin/header_admin.php';
require_once '../../templates/admin/sidenav_admin.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
    body {
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
        margin-bottom: 25px;
    }

    h2 i {
        color: #2e78c6;
        margin-right: 10px;
    }

    h5 {
        font-size: 18px;
        font-weight: 600;
        color: #101518;
        margin-bottom: 0;
    }

    h5 i {
        margin-right: 8px;
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
    }

    .card-header.bg-info {
        background-color: #17a2b8 !important;
    }

    .card-body {
        padding: 25px;
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

    .form-label.fw-bold {
        font-weight: 600;
    }

    .form-control {
        border-radius: 0.5rem;
        border: 1px solid #d1d5db;
        padding: 12px 16px;
        font-size: 14px;
        color: #101518;
        background-color: white;
        transition: all 0.3s ease;
    }

    .form-control:focus {
        border-color: #2e78c6;
        box-shadow: 0 0 0 3px rgba(46, 120, 198, 0.1);
        outline: none;
    }

    .form-control::placeholder {
        color: #9ca3af;
    }

    small.text-muted {
        font-size: 13px;
        color: #6b7280;
        margin-top: 4px;
        display: block;
    }

    /* Button styling */
    .btn {
        border-radius: 9999px;
        font-weight: 500;
        font-size: 0.875rem;
        padding: 12px 24px;
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
        box-shadow: 0 4px 12px rgba(46, 120, 198, 0.2);
    }

    .btn-primary i {
        margin-right: 8px;
    }

    .btn.w-100 {
        padding: 12px;
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

    .table-light {
        background-color: #eaedf1;
    }

    .table-light th {
        color: #101518;
        font-weight: 600;
        font-size: 14px;
        padding: 16px 12px;
        border-bottom: 2px solid #d1d5db;
    }

    .table-light th i {
        color: #2e78c6;
        margin-right: 6px;
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

    /* Row layout */
    .row {
        margin-bottom: 0;
    }

    .col-lg-4,
    .col-lg-8 {
        margin-bottom: 25px;
    }

    /* Container spacing */
    .container-fluid.py-4 {
        padding-top: 20px;
        padding-bottom: 20px;
    }

    /* Icon styling */
    .fas {
        font-size: 16px;
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
            margin-bottom: 20px;
        }

        h5 {
            font-size: 16px;
        }

        .card-body {
            padding: 20px;
        }

        .card-header {
            padding: 15px 20px;
        }

        .col-lg-4,
        .col-lg-8 {
            width: 100%;
            margin-bottom: 20px;
        }

        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .table-light th,
        .table td {
            padding: 12px 8px;
            font-size: 13px;
            white-space: nowrap;
        }

        .btn {
            width: 100%;
            justify-content: center;
        }

        .form-control {
            padding: 10px 14px;
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

        h5 {
            font-size: 17px;
        }

        .table-light th,
        .table td {
            padding: 14px 10px;
            font-size: 13.5px;
        }
    }

    @media (min-width: 1024px) {
        .main-content-wrapper {
            margin-left: 20%;
            padding: 20px 35px;
        }

        .col-lg-4 {
            padding-right: 15px;
        }

        .col-lg-8 {
            padding-left: 15px;
        }
    }

    /* Additional utility classes */
    .mb-4 {
        margin-bottom: 25px !important;
    }

    .mb-3 {
        margin-bottom: 20px !important;
    }

    .mb-0 {
        margin-bottom: 0 !important;
    }

    .py-4 {
        padding-top: 25px;
        padding-bottom: 25px;
    }

    .me-2 {
        margin-right: 8px;
    }

    /* Input group for better password visibility toggle (future enhancement) */
    .input-group {
        position: relative;
    }

    .input-group .form-control {
        padding-right: 45px;
    }

    .password-toggle {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #6b7280;
        cursor: pointer;
        z-index: 10;
    }
</style>

<div class="main-content-wrapper">
    <div class="container-fluid py-4">
        <h2 class="mb-4"><i class="fas fa-users-cog"></i> Admin Management</h2>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= htmlspecialchars($message_type) ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Create New Admin Form -->
            <div class="col-lg-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-user-plus"></i> Create New Admin</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="create_admin">

                            <div class="mb-3">
                                <label for="name" class="form-label fw-bold">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name" required
                                    value="<?= isset($name) ? htmlspecialchars($name) : '' ?>"
                                    placeholder="Enter full name">
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label fw-bold">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" required
                                    value="<?= isset($email) ? htmlspecialchars($email) : '' ?>"
                                    placeholder="Enter email">
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label fw-bold">Password</label>
                                <small class="d-block text-muted mb-2">Minimum 8 characters</small>
                                <input type="password" class="form-control" id="password" name="password" required
                                    placeholder="Enter password">
                            </div>

                            <div class="mb-3">
                                <label for="password_confirm" class="form-label fw-bold">Confirm Password</label>
                                <input type="password" class="form-control" id="password_confirm"
                                    name="password_confirm" required placeholder="Confirm password">
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-check"></i> Create Admin Account
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Admin Accounts List -->
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-list"></i> Admin Accounts (<?= count($admin_accounts) ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($admin_accounts)): ?>
                            <div class="alert alert-warning" role="alert">
                                No admin accounts found.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th><i class="fas fa-id-card"></i> ID</th>
                                            <th><i class="fas fa-user"></i> Name</th>
                                            <th><i class="fas fa-envelope"></i> Email</th>
                                            <th><i class="fas fa-calendar"></i> Created</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($admin_accounts as $admin): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($admin['id']) ?></td>
                                                <td><?= htmlspecialchars($admin['name']) ?></td>
                                                <td><?= htmlspecialchars($admin['email']) ?></td>
                                                <td>
                                                    <?php
                                                    $date = new DateTime($admin['created_at']);
                                                    echo $date->format('M d, Y H:i');
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

<script src="../../assets/js/jquery.min.js"></script>
<script src="../../assets/js/script.js"></script>

<script>
    // Add password visibility toggle functionality
    document.addEventListener('DOMContentLoaded', function () {
        const passwordInputs = document.querySelectorAll('input[type="password"]');

        passwordInputs.forEach(input => {
            const wrapper = document.createElement('div');
            wrapper.className = 'input-group';
            input.parentNode.insertBefore(wrapper, input);
            wrapper.appendChild(input);

            const toggleBtn = document.createElement('button');
            toggleBtn.type = 'button';
            toggleBtn.className = 'password-toggle';
            toggleBtn.innerHTML = '<i class="fas fa-eye"></i>';
            wrapper.appendChild(toggleBtn);

            toggleBtn.addEventListener('click', function () {
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
            });
        });
    });
</script>

<?php require_once '../../templates/footer.php'; ?>