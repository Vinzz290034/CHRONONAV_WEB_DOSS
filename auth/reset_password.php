<?php
require_once '../config/db_connect.php'; // Your database connection

/** @var \mysqli $conn */ 
$message = '';
$message_type = '';
$token = $_GET['token'] ?? ''; // Get the token from the URL
$user_id = null; // We'll store the user ID here if the token is valid

// ----------------------------------------------------------------------
// 1. TOKEN VALIDATION AND INITIAL LOAD
// ----------------------------------------------------------------------

if (empty($token)) {
    $message = 'Error: The password reset link is missing the required token.';
    $message_type = 'danger';
    // Exit here as we cannot proceed without a token
} else {
    // Hash the raw token from the URL to look it up in the database
    // This MUST match the hashing algorithm used in forgot_password.php (sha256)
    $tokenHash = hash('sha256', $token); 
    $currentTime = date('Y-m-d H:i:s');

    $stmt = $conn->prepare("SELECT user_id, expires_at FROM password_resets WHERE token_hash = ?");
    if ($stmt) {
        $stmt->bind_param("s", $tokenHash);
        $stmt->execute();
        $result = $stmt->get_result();
        $reset_data = $result->fetch_assoc();
        $stmt->close();

        if (!$reset_data) {
            // IMPORTANT: Keep this message vague to prevent user enumeration attacks
            $message = 'Error: This password reset token is invalid or has already been used.';
            $message_type = 'danger';
        } elseif (strtotime($reset_data['expires_at']) < time()) {
            $message = 'Error: This password reset link has expired.';
            $message_type = 'danger';
            
            // OPTIONAL: Delete the expired token here to keep the table clean
            if (isset($reset_data['user_id'])) {
                 $conn->query("DELETE FROM password_resets WHERE user_id = {$reset_data['user_id']}");
            }
        } else {
            // Token is VALID! Store the user ID and allow form submission.
            $user_id = $reset_data['user_id'];
        }
    } else {
        $message = 'Database error during token lookup. Please try again later.';
        $message_type = 'danger';
        error_log("Reset password DB prepare failed (lookup): " . $conn->error);
    }
}


// ----------------------------------------------------------------------
// 2. PASSWORD UPDATE LOGIC (If token is valid AND form is submitted)
// ----------------------------------------------------------------------

if ($user_id && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($password) || empty($confirm_password)) {
        $message = 'Please fill in both password fields.';
        $message_type = 'danger';
    } elseif ($password !== $confirm_password) {
        $message = 'The passwords do not match.';
        $message_type = 'danger';
    } elseif (strlen($password) < 8) { // Basic password policy check
        $message = 'Your password must be at least 8 characters long.';
        $message_type = 'danger';
    } else {
        // Start a transaction for atomicity
        $conn->begin_transaction();

        try {
            // A. Hash the new password securely
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            // B. Update the user's password
            $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update_stmt->bind_param("si", $hashed_password, $user_id);
            $update_success = $update_stmt->execute();
            $update_stmt->close();

            // C. Delete the used reset token to prevent reuse
            $delete_stmt = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
            $delete_stmt->bind_param("i", $user_id);
            $delete_success = $delete_stmt->execute();
            $delete_stmt->close();

            if ($update_success && $delete_success) {
                $conn->commit();
                $message = 'Success! Your password has been reset. You can now log in.';
                $message_type = 'success';
                // Crucial step: Invalidate the token so the form disappears
                $user_id = null; 
            } else {
                $conn->rollback();
                $message = 'Password update failed. Please try again.';
                $message_type = 'danger';
                error_log("Password reset failed (update/delete): " . $conn->error);
            }
        } catch (\Exception $e) {
            $conn->rollback();
            $message = 'A critical error occurred during password change.';
            $message_type = 'danger';
            error_log("Password reset transaction failed: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - ChronoNav</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>
    <link rel="stylesheet" as="style" onload="this.rel='stylesheet'"
        href="https://fonts.googleapis.com/css2?display=swap&family=Noto+Sans:wght@400;500;700;900&family=Space+Grotesk:wght@400;500;700">

    <link rel="icon" type="image/x-icon"
        href="https://res.cloudinary.com/deua2yipj/image/upload/v1758917007/ChronoNav_logo_muon27.png">

    <style>
        .auth-forgot-body {
            font-family: 'space grotesk', 'noto sans', sans-serif;
            background: linear-gradient(rgba(62, 153, 244, 0.85), rgba(6, 168, 249, 0.85)),
                url('https://res.cloudinary.com/deua2yipj/image/upload/v1759258431/chrononav_bg_l38ntk.png');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: #111418;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            margin: 0;
        }

        .auth-forgot-container {
            width: 100%;
            max-width: 420px;
        }

        .auth-forgot-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            padding: 2.5rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            animation: authFadeInUp 0.6s ease-out; /* Keep the animation */
        }

        .auth-logo-section {
            text-align: center;
            margin-bottom: 2rem;
        }

        .auth-brand-tagline {
            color: #5f7d8c;
            font-size: 0.9rem;
            margin-bottom: 0;
        }

        .auth-form-group {
            margin-bottom: 1.5rem;
        }

        .auth-form-label {
            font-weight: 500;
            color: #111418;
            margin-bottom: 0.5rem;
            display: block;
        }

        .auth-form-control {
            background-color: rgba(248, 249, 250, 0.95);
            border: 1px solid #dbe2e6;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            height: 48px;
            width: 100%;
            display: block;
            font-size: 1rem;
            color: #333;
            transition: border-color 0.25s ease, box-shadow 0.25s ease;
        }

        .auth-form-control:focus {
            background-color: rgba(248, 249, 250, 1);
            border-color: #3e99f4;
            outline: none;
            box-shadow: 0 0 0 3px rgba(62, 153, 244, 0.2);
        }

        .auth-btn-reset {
            background-color: #06a8f9;
            color: white;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            height: 48px;
            width: 100%;
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
        }

        .auth-btn-reset:hover {
            background-color: #0588d1;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(6, 168, 249, 0.3);
        }

        .auth-alert {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }

        .auth-message {
            background-color: #d1edff;
            color: #05547e;
            border: 1px solid #b3d9ff;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }

        .auth-links-section {
            text-align: center;
            margin-top: 1.5rem;
        }

        .auth-link {
            color: #3e99f4;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .auth-link:hover {
            color: #0588d1;
            text-decoration: underline;
        }
        
        @keyframes authFadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body class="auth-forgot-body">
    <div class="auth-forgot-container">
        <div class="auth-forgot-card">
            <div class="auth-logo-section">
                <h1 class="auth-brand-name text-black-50">ChronoNav</h1>
                <p class="auth-brand-tagline">Set Your New Password</p>
            </div>

            <?php if ($message): ?>
                <div class="<?= $message_type === 'success' ? 'auth-message' : 'auth-alert' ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <?php if ($user_id): // Only show the form if the token is VALID ?>
                <form method="POST" id="resetForm">
                    <div class="auth-form-group">
                        <label for="password" class="auth-form-label fw-bold text-black-50">New Password</label>
                        <input type="password" class="auth-form-control" id="password" name="password"
                            placeholder="Enter new password" required>
                    </div>

                    <div class="auth-form-group">
                        <label for="confirm_password" class="auth-form-label fw-bold text-black-50">Confirm Password</label>
                        <input type="password" class="auth-form-control" id="confirm_password" name="confirm_password"
                            placeholder="Confirm new password" required>
                    </div>

                    <button type="submit" class="auth-btn-reset">Reset Password</button>
                </form>
            <?php endif; ?>
            
            <?php if (!$user_id || $message_type === 'success'): // Show link back to login if invalid token OR after successful reset ?>
                <div class="auth-links-section">
                    <a href="login.php" class="auth-link">
                        <i class="fas fa-sign-in-alt me-2"></i>Go to Login
                    </a>
                </div>
            <?php endif; ?>

            <p class="auth-terms-text"
                style="text-align: center; color: #5f7d8c; font-size: 0.8rem; margin-top: 1.5rem; line-height: 1.4;">
                Please choose a strong, unique password for your account.
            </p>
        </div>
        
        </div>
    
    <script>
        // Form submission feedback
        document.getElementById('resetForm')?.addEventListener('submit', function (e) {
            const submitBtn = this.querySelector('.auth-btn-reset');

            // Simple client-side check before server submission
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                alert('Error: Passwords do not match.');
                e.preventDefault();
                return;
            }
            if (password.length < 8) {
                alert('Error: Your password must be at least 8 characters long.');
                e.preventDefault();
                return;
            }

            // Show loading state if client-side validation passes
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Updating...';
            submitBtn.disabled = true;
        });
    </script>
</body>

</html>