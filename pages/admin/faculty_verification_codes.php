<?php
// pages/admin/faculty_verification_codes.php
// Manage faculty verification codes for faculty registration

require_once '../../middleware/auth_check.php';
require_once '../../config/db_connect.php';

// Restrict access to admin only
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../auth/logout.php");
    exit();
}

/** @var \mysqli $conn */

$user = $_SESSION['user'];
$page_title = "Faculty Verification Codes";
$current_page = "faculty_verification_codes";
$display_name = htmlspecialchars($user['name'] ?? 'Admin');

// Handle form submissions
$message = '';
$message_type = '';

// Generate new faculty verification code
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'generate_code') {
        $description = trim($_POST['description'] ?? '');
        $expires_at = !empty($_POST['expires_at']) ? $_POST['expires_at'] . ' 23:59:59' : null;
        
        if (empty($description)) {
            $message = "Please provide a description for the verification code.";
            $message_type = "danger";
        } else {
            // Generate unique verification code
            $verification_code = strtoupper(bin2hex(random_bytes(6))); // 12-character code
            
            $stmt = $conn->prepare("
                INSERT INTO faculty_verification_codes (verification_code, description, created_by, expires_at) 
                VALUES (?, ?, ?, ?)
            ");
            
            if ($stmt) {
                $user_id = $user['id'];
                $stmt->bind_param("ssss", $verification_code, $description, $user_id, $expires_at);
                
                if ($stmt->execute()) {
                    $message = "Faculty verification code generated successfully: <strong>$verification_code</strong>";
                    $message_type = "success";
                } else {
                    $message = "Error generating code: " . $stmt->error;
                    $message_type = "danger";
                }
                $stmt->close();
            } else {
                $message = "Database error: " . $conn->error;
                $message_type = "danger";
            }
        }
    } elseif ($action === 'revoke_code') {
        $code_id = $_POST['code_id'] ?? null;
        
        if ($code_id) {
            // Mark code as expired by setting used_at to now (or soft delete)
            $stmt = $conn->prepare("UPDATE faculty_verification_codes SET expires_at = NOW() WHERE id = ?");
            
            if ($stmt) {
                $stmt->bind_param("i", $code_id);
                
                if ($stmt->execute()) {
                    $message = "Faculty verification code has been revoked.";
                    $message_type = "success";
                } else {
                    $message = "Error revoking code: " . $stmt->error;
                    $message_type = "danger";
                }
                $stmt->close();
            }
        }
    }
}

// Fetch all verification codes
$codes_query = "
    SELECT 
        fvc.id,
        fvc.verification_code,
        fvc.description,
        fvc.is_used,
        fvc.created_by,
        fvc.created_at,
        fvc.expires_at,
        fvc.used_at,
        u_creator.name as creator_name,
        u_used.name as used_by_name
    FROM faculty_verification_codes fvc
    LEFT JOIN users u_creator ON fvc.created_by = u_creator.id
    LEFT JOIN users u_used ON fvc.used_by_user_id = u_used.id
    ORDER BY fvc.created_at DESC
";

$codes_result = $conn->query($codes_query);
$codes = $codes_result->fetch_all(MYSQLI_ASSOC) ?? [];

require_once '../../templates/admin/header_admin.php';
require_once '../../templates/admin/sidenav_admin.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - ChronoNav</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300..700&display=swap" rel="stylesheet">
    
    <link rel="icon" type="image/x-icon"
        href="https://res.cloudinary.com/deua2yipj/image/upload/v1758917007/ChronoNav_logo_muon27.png">

    <style>
        body {
            font-family: "Space Grotesk", "Noto Sans", sans-serif;
            background-color: #f8f9fa;
        }

        .main-content-wrapper {
            margin-left: 20%;
            transition: margin-left 0.3s ease;
            padding: 2rem;
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-header h1 {
            color: #0e151b;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .page-header p {
            color: #5f7d8c;
            margin-bottom: 0;
        }

        .alert {
            border-radius: 8px;
            border: none;
            margin-bottom: 2rem;
        }

        .card {
            border: 1px solid #e0e7ff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }

        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #e0e7ff;
            padding: 1.5rem;
        }

        .card-header h5 {
            margin: 0;
            color: #0e151b;
            font-weight: 600;
        }

        .card-body {
            padding: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            font-weight: 500;
            color: #0e151b;
            margin-bottom: 0.5rem;
        }

        .form-control, .form-select {
            border: 1px solid #dbe2e6;
            border-radius: 6px;
            padding: 0.75rem 1rem;
            transition: border-color 0.25s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: #06a8f9;
            box-shadow: 0 0 0 3px rgba(6, 168, 249, 0.1);
        }

        .btn-primary {
            background-color: #06a8f9;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #0588d1;
            transform: translateY(-2px);
        }

        .table {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
        }

        .table th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #e0e7ff;
            font-weight: 600;
            color: #0e151b;
            padding: 1rem;
        }

        .table td {
            padding: 1rem;
            border-bottom: 1px solid #e0e7ff;
            color: #333;
        }

        .badge {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-weight: 500;
        }

        .badge-success {
            background-color: #d4edda;
            color: #155724;
        }

        .badge-warning {
            background-color: #fff3cd;
            color: #856404;
        }

        .badge-danger {
            background-color: #f8d7da;
            color: #721c24;
        }

        .code-display {
            font-family: 'Courier New', monospace;
            background-color: #f8f9fa;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            font-weight: 600;
            color: #06a8f9;
        }

        .btn-action {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            border-radius: 4px;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-revoke {
            background-color: #f8d7da;
            color: #721c24;
            border: none;
        }

        .btn-revoke:hover {
            background-color: #f5c6cb;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            border-left: 4px solid #06a8f9;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: #06a8f9;
        }

        .stat-label {
            color: #5f7d8c;
            font-size: 0.875rem;
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .main-content-wrapper {
                margin-left: 0;
                padding: 1rem;
            }

            .page-header h1 {
                font-size: 22px;
            }
        }
    </style>
</head>
<body>
    <div class="main-content-wrapper">
        <div class="page-header">
            <h1><i class="fas fa-key"></i> Faculty Verification Codes</h1>
            <p>Generate and manage verification codes for faculty registration</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                <?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?= count($codes) ?></div>
                <div class="stat-label">Total Codes Generated</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= count(array_filter($codes, fn($c) => !$c['is_used'])) ?></div>
                <div class="stat-label">Available Codes</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= count(array_filter($codes, fn($c) => $c['is_used'])) ?></div>
                <div class="stat-label">Used Codes</div>
            </div>
        </div>

        <!-- Generate New Code Form -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-plus-circle"></i> Generate New Faculty Verification Code</h5>
            </div>
            <div class="card-body">
                <form method="POST" class="row g-3">
                    <input type="hidden" name="action" value="generate_code">
                    
                    <div class="col-md-6">
                        <label for="description" class="form-label">Description / Purpose <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="description" name="description" 
                            placeholder="e.g., Mathematics Faculty - Spring 2025" required>
                        <small class="form-text text-muted">Brief description of what this code is for</small>
                    </div>

                    <div class="col-md-6">
                        <label for="expires_at" class="form-label">Expiration Date (Optional)</label>
                        <input type="date" class="form-control" id="expires_at" name="expires_at">
                        <small class="form-text text-muted">Leave empty for no expiration</small>
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-key"></i> Generate Code
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Verification Codes Table -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-list"></i> Generated Codes</h5>
            </div>
            <div class="card-body">
                <?php if (empty($codes)): ?>
                    <p class="text-muted text-center mb-0">No verification codes generated yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Created By</th>
                                    <th>Created Date</th>
                                    <th>Expires</th>
                                    <th>Used By Faculty</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($codes as $code): 
                                    $is_expired = !empty($code['expires_at']) && strtotime($code['expires_at']) < time();
                                    $status = $is_expired ? 'Expired' : ($code['is_used'] ? 'Used' : 'Available');
                                    $status_badge = $is_expired ? 'danger' : ($code['is_used'] ? 'success' : 'warning');
                                ?>
                                    <tr>
                                        <td><span class="code-display"><?= htmlspecialchars($code['verification_code']) ?></span></td>
                                        <td><?= htmlspecialchars($code['description']) ?></td>
                                        <td><span class="badge badge-<?= $status_badge ?>"><?= $status ?></span></td>
                                        <td><?= htmlspecialchars($code['creator_name'] ?? 'Unknown') ?></td>
                                        <td><?= date('M d, Y H:i', strtotime($code['created_at'])) ?></td>
                                        <td>
                                            <?php if (empty($code['expires_at'])): ?>
                                                <span class="text-muted">No expiration</span>
                                            <?php else: ?>
                                                <?= date('M d, Y', strtotime($code['expires_at'])) ?>
                                                <?php if ($is_expired): ?>
                                                    <span class="badge bg-danger">Expired</span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($code['is_used']): ?>
                                                <?= htmlspecialchars($code['used_by_name'] ?? 'Unknown') ?>
                                                <br>
                                                <small class="text-muted"><?= date('M d, Y', strtotime($code['used_at'])) ?></small>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!$code['is_used'] && !$is_expired): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="revoke_code">
                                                    <input type="hidden" name="code_id" value="<?= $code['id'] ?>">
                                                    <button type="submit" class="btn btn-action btn-revoke" 
                                                        onclick="return confirm('Revoke this code?');">
                                                        <i class="fas fa-ban"></i> Revoke
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
