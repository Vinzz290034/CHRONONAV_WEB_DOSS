<?php
/**
 * Admin Account Setup Page
 * This script creates/verifies the admin account in the database
 * Access this once after database setup: /setup/create_admin.php
 */

session_start();

// Check if database connection works
require_once __DIR__ . '/../config/db_connect.php';

$message = '';
$status = '';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_admin'])) {
    try {
        // First, delete any existing admins
        $delete_query = "DELETE FROM users WHERE role = 'admin'";
        if ($conn->query($delete_query)) {
            $deleted_rows = $conn->affected_rows;
            $message .= "Removed $deleted_rows existing admin account(s). ";
        }
        
        // Create the new admin account
        $admin_name = "Vince Andrew Santoya";
        $admin_email = "hanssantoya@gmail.com";
        $admin_id = "ADMN001";
        $admin_password = "vince123456";
        $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("
            INSERT INTO users (name, email, password, role, admin_id, course, department, is_active, status) 
            VALUES (?, ?, ?, 'admin', ?, NULL, NULL, 1, 'active')
        ");
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("ssss", $admin_name, $admin_email, $hashed_password, $admin_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $new_admin_id = $conn->insert_id;
        $stmt->close();
        
        $message = "✓ Admin account created successfully!";
        $status = "success";
        
    } catch (Exception $e) {
        $message = "✗ Error: " . $e->getMessage();
        $status = "error";
    }
}

// Check if admin already exists
$admin_exists = false;
$admin_count = 0;
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
if ($result) {
    $row = $result->fetch_assoc();
    $admin_count = $row['count'];
    $admin_exists = $admin_count > 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChronoNav - Admin Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/x-icon"
        href="https://res.cloudinary.com/deua2yipj/image/upload/v1758917007/ChronoNav_logo_muon27.png">
    
    <style>
        body {
            background: linear-gradient(135deg, #3e99f4 0%, #06a8f9 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: "Space Grotesk", "Noto Sans", sans-serif;
        }
        
        .setup-container {
            background: white;
            padding: 3rem;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            max-width: 500px;
            width: 100%;
        }
        
        .setup-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .setup-header h1 {
            font-size: 28px;
            font-weight: 700;
            color: #0e151b;
            margin-bottom: 0.5rem;
        }
        
        .setup-header p {
            color: #5f7d8c;
            margin: 0;
        }
        
        .logo {
            width: 60px;
            height: 60px;
            margin: 0 auto 1rem;
        }
        
        .alert {
            border-radius: 8px;
            border: none;
            margin-bottom: 2rem;
        }
        
        .btn-setup {
            background-color: #06a8f9;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .btn-setup:hover {
            background-color: #0588d1;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(6, 168, 249, 0.3);
            color: white;
        }
        
        .info-box {
            background: #f8f9fa;
            border: 1px solid #dbe2e6;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .info-box h6 {
            font-weight: 600;
            color: #0e151b;
            margin-bottom: 1rem;
        }
        
        .info-item {
            margin-bottom: 0.75rem;
            font-size: 0.9rem;
        }
        
        .info-label {
            color: #5f7d8c;
            font-weight: 500;
        }
        
        .info-value {
            color: #0e151b;
            font-weight: 600;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .status-active {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="setup-header">
            <div class="logo">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="100%" height="100%">
                    <image
                        href="https://res.cloudinary.com/deua2yipj/image/upload/v1758917007/ChronoNav_logo_muon27.png"
                        x="0" y="0" width="100" height="100" />
                </svg>
            </div>
            <h1>ChronoNav Setup</h1>
            <p>Admin Account Initialization</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $status === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="info-box">
            <h6><i class="fas fa-info-circle"></i> Admin Account Information</h6>
            <div class="info-item">
                <span class="info-label">Full Name:</span>
                <span class="info-value">Vince Andrew Santoya</span>
            </div>
            <div class="info-item">
                <span class="info-label">Email:</span>
                <span class="info-value">hanssantoya@gmail.com</span>
            </div>
            <div class="info-item">
                <span class="info-label">Admin ID:</span>
                <span class="info-value">ADMN001</span>
            </div>
            <div class="info-item">
                <span class="info-label">Password:</span>
                <span class="info-value">vince123456</span>
            </div>
            <div class="info-item">
                <span class="info-label">Status:</span>
                <span class="status-badge status-active">Active</span>
            </div>
        </div>

        <?php if ($admin_exists): ?>
            <div class="alert alert-info">
                <i class="fas fa-check-circle"></i>
                <strong>Admin account already exists</strong>
                <p class="mb-0 mt-2">Found <?= $admin_count ?> admin account(s) in the database.</p>
            </div>
            <p class="text-center text-muted mb-0">
                <a href="../auth/login.php" class="link-primary">Go to login page →</a>
            </p>
        <?php else: ?>
            <form method="POST">
                <button type="submit" name="create_admin" value="1" class="btn btn-setup">
                    <i class="fas fa-user-shield"></i> Create Admin Account
                </button>
            </form>
            <p class="text-center text-muted mt-3 mb-0">
                <small>Click the button above to initialize the admin account</small>
            </p>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</body>
</html>
