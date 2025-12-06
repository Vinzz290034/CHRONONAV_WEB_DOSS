<?php
// CHRONONAV_WEB_DOSS/pages/admin/manage_admins.php
// Admin user management page - allows admins to create/manage other admin accounts

require_once '../../config/db_connect.php';
require_once '../../middleware/auth_check.php';

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
                                    value="<?= isset($name) ? htmlspecialchars($name) : '' ?>" placeholder="Enter full name">
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label fw-bold">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" required 
                                    value="<?= isset($email) ? htmlspecialchars($email) : '' ?>" placeholder="Enter email">
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label fw-bold">Password</label>
                                <small class="d-block text-muted mb-2">Minimum 8 characters</small>
                                <input type="password" class="form-control" id="password" name="password" required placeholder="Enter password">
                            </div>

                            <div class="mb-3">
                                <label for="password_confirm" class="form-label fw-bold">Confirm Password</label>
                                <input type="password" class="form-control" id="password_confirm" name="password_confirm" required placeholder="Confirm password">
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

<style>
    .main-content-wrapper {
        margin-left: 250px;
        padding: 20px;
        min-height: 100vh;
        background-color: #f8f9fa;
    }

    .card {
        border: none;
        border-radius: 0.5rem;
        margin-bottom: 2rem;
    }

    .card-header {
        border-bottom: 2px solid rgba(0, 0, 0, 0.1);
        padding: 1rem 1.5rem;
    }

    .card-body {
        padding: 1.5rem;
    }

    .form-label {
        margin-bottom: 0.5rem;
        font-size: 0.95rem;
    }

    .form-control {
        border-radius: 0.375rem;
        border: 1px solid #dee2e6;
        padding: 0.75rem;
        font-size: 0.95rem;
    }

    .form-control:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }

    .table-hover tbody tr:hover {
        background-color: #f5f5f5;
        cursor: pointer;
    }

    .btn {
        padding: 0.75rem 1.5rem;
        border-radius: 0.375rem;
        font-weight: 500;
    }

    h2 {
        color: #333;
        margin-bottom: 1.5rem;
        font-weight: 600;
    }

    h5 {
        color: #333;
        font-weight: 600;
    }

    .mb-0 {
        margin-bottom: 0;
    }

    @media (max-width: 992px) {
        .main-content-wrapper {
            margin-left: 0;
            padding: 15px;
        }

        .row {
            margin-bottom: 1rem;
        }
    }

    @media (max-width: 768px) {
        .card {
            margin-bottom: 1.5rem;
        }

        .form-label {
            font-size: 0.9rem;
        }
    }
</style>

<?php require_once '../../templates/footer.php'; ?>
