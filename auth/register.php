<?php
session_start();
require_once '../config/db_connect.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    $course = trim($_POST['course']);
    $dept = trim($_POST['department']);
    $faculty_verification_code = trim($_POST['faculty_verification_code'] ?? '');

    // Validate basic fields
    if (empty($name) || empty($email) || empty($password) || empty($role)) {
        $error = "Name, email, password, and account type are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($role === 'faculty' && empty($faculty_verification_code)) {
        $error = "Faculty ID is required to register as Faculty.";
    } elseif ($role === 'user' && (empty($course) || empty($dept))) {
        $error = "Course and Department are required for Student accounts.";
    } else {
        // Check if email already exists
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = "Email already registered. Please login or use another.";
        } else {
            // If role is faculty, verify the faculty code
            $faculty_id = null;
            if ($role === 'faculty') {
                $code_check = $conn->prepare("
                    SELECT id FROM faculty_verification_codes 
                    WHERE verification_code = ? 
                    AND is_used = 0 
                    AND (expires_at IS NULL OR expires_at > NOW())
                ");
                
                if ($code_check) {
                    $code_check->bind_param("s", $faculty_verification_code);
                    $code_check->execute();
                    $code_result = $code_check->get_result();
                    
                    if ($code_result->num_rows === 0) {
                        $error = "Invalid, expired, or already used Faculty ID. Please contact your administrator.";
                    } else {
                        $faculty_id = $faculty_verification_code;
                    }
                    $code_check->close();
                } else {
                    $error = "Database error: " . $conn->error;
                }
            }
            
            // If no errors so far, proceed with registration
            if (empty($error)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, course, department, faculty_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
                
                if ($stmt) {
                    $stmt->bind_param("sssssss", $name, $email, $hashed_password, $role, $course, $dept, $faculty_id);
                    
                    if ($stmt->execute()) {
                        // If faculty, mark the verification code as used
                        if ($role === 'faculty') {
                            $user_id = $conn->insert_id;
                            $update_code = $conn->prepare("
                                UPDATE faculty_verification_codes 
                                SET is_used = 1, used_by_user_id = ?, used_at = NOW() 
                                WHERE verification_code = ?
                            ");
                            if ($update_code) {
                                $update_code->bind_param("is", $user_id, $faculty_verification_code);
                                $update_code->execute();
                                $update_code->close();
                            }
                        }
                        $message = "Registration successful! <a href='login.php' class='auth-link'>Login here</a>.";
                    } else {
                        $error = "Error during registration: " . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    $error = "Database error: " . $conn->error;
                }
            }
        }
        $check_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - ChronoNav</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>
    <link rel="stylesheet" as="style" onload="this.rel='stylesheet'"
        href="https://fonts.googleapis.com/css2?display=swap&family=Noto+Sans:wght@400;500;700;900&family=Space+Grotesk:wght@400;500;700">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon"
        href="https://res.cloudinary.com/deua2yipj/image/upload/v1758917007/ChronoNav_logo_muon27.png">

    <style>
        /* Register Page Specific Styles */
        .auth-register-body {
            font-family: "Space Grotesk", "Noto Sans", sans-serif;
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

        .auth-register-container {
            width: 100%;
            max-width: 480px;
        }

        .auth-register-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            padding: 2.5rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .auth-logo-section {
            text-align: center;
            margin-bottom: 2rem;
        }

        .auth-logo {
            width: 60px;
            height: 60px;
            margin: 0 auto 1rem;
        }

        .auth-brand-name {
            font-size: 1.75rem;
            font-weight: 700;
            color: #111418;
            margin-bottom: 0.5rem;
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

        .auth-form-select {
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

        .auth-form-select:focus {
            background-color: rgba(248, 249, 250, 1);
            border-color: #3e99f4;
            outline: none;
            box-shadow: 0 0 0 3px rgba(62, 153, 244, 0.2);
        }

        .auth-password-input {
            position: relative;
        }

        .auth-password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #5f7d8c;
            cursor: pointer;
        }

        .auth-btn-register {
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

        .auth-btn-register:hover {
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

        .auth-message a {
            color: #05547e;
            font-weight: 600;
            text-decoration: none;
        }

        .auth-message a:hover {
            text-decoration: underline;
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

        .auth-separator {
            margin: 0 0.5rem;
            color: #5f7d8c;
        }

        .auth-terms-text {
            text-align: center;
            color: #5f7d8c;
            font-size: 0.8rem;
            margin-top: 1.5rem;
            line-height: 1.4;
        }

        .auth-footer {
            text-align: center;
            margin-top: 2rem;
            color: white;
            font-size: 0.8rem;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        }

        .auth-footer a {
            color: white;
            text-decoration: none;
            font-weight: 500;
        }

        .auth-footer a:hover {
            text-decoration: underline;
        }

        /* Modal Styles for Auth Pages */
        .auth-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .auth-modal-content {
            max-width: 800px;
            width: 100%;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
        }

        .auth-modal-header {
            background: linear-gradient(135deg, #3e99f4, #06a8f9);
            color: white;
            padding: 1.5rem 2rem;
            position: relative;
        }

        .auth-modal-body {
            padding: 2rem;
            overflow-y: auto;
            flex-grow: 1;
        }

        .auth-modal-footer {
            padding: 1.5rem 2rem;
            background-color: #f0f2f5;
            border-top: 1px solid #dbe2e6;
            display: flex;
            justify-content: flex-end;
        }

        .auth-modal-close {
            position: absolute;
            top: 1.5rem;
            right: 2rem;
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .auth-modal-close:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.05);
        }

        @media (max-width: 480px) {
            .auth-register-card {
                padding: 2rem 1.5rem;
            }

            .auth-logo {
                width: 50px;
                height: 50px;
            }

            .auth-brand-name {
                font-size: 1.5rem;
            }

            .auth-register-body {
                padding: 15px;
                background-attachment: scroll;
            }

            .auth-modal {
                padding: 10px;
            }

            .auth-modal-body {
                padding: 1.5rem;
            }

            .auth-modal-header,
            .auth-modal-footer {
                padding: 1.25rem 1.5rem;
            }

            .auth-modal-close {
                top: 1.25rem;
                right: 1.5rem;
            }
        }

        /* Animation for better visual appeal */
        .auth-register-card {
            animation: authFadeInUp 0.6s ease-out;
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

<body class="auth-register-body">
    <div class="auth-register-container">
        <div class="auth-register-card">
            <div class="auth-logo-section">
                <div class="auth-logo">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="100%" height="100%">
                        <image
                            href="https://res.cloudinary.com/deua2yipj/image/upload/v1758917007/ChronoNav_logo_muon27.png"
                            x="0" y="0" width="100" height="100" />
                    </svg>
                </div>
                <h1 class="auth-brand-name text-black-50">ChronoNav</h1>
                <p class="auth-brand-tagline">Create your account</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="auth-alert"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if (!empty($message)): ?>
                <div class="auth-message"><?= $message ?></div>
            <?php endif; ?>

            <form method="POST" id="registerForm">
                <div class="auth-form-group">
                    <label for="name" class="auth-form-label fw-bold text-black-50">Full Name</label>
                    <input type="text" class="auth-form-control" id="name" name="name"
                        placeholder="Enter your full name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                        required>
                </div>

                <div class="auth-form-group">
                    <label for="email" class="auth-form-label fw-bold text-black-50">Email Address</label>
                    <input type="email" class="auth-form-control" id="email" name="email"
                        placeholder="Enter your email address" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        required>
                </div>

                <div class="auth-form-group">
                    <label for="password" class="auth-form-label fw-bold text-black-50">Password</label>
                    <div class="auth-password-input">
                        <input type="password" class="auth-form-control" id="password" name="password"
                            placeholder="Create a password (min. 6 characters)" required>
                        <button type="button" class="auth-password-toggle" id="togglePassword">
                            <i class="far fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="auth-form-group">
                    <label for="role" class="auth-form-label fw-bold text-black-50">Account Type</label>
                    <select class="auth-form-select" id="role" name="role" required>
                        <option value="user" <?= (($_POST['role'] ?? '') == 'user') ? 'selected' : '' ?>>Student</option>
                        <option value="faculty" <?= (($_POST['role'] ?? '') == 'faculty') ? 'selected' : '' ?>>Faculty</option>
                    </select>
                </div>

                <!-- Faculty Verification Code Field - Hidden by default -->
                <div class="auth-form-group" id="facultyVerificationGroup" style="display: none;">
                    <label for="faculty_verification_code" class="auth-form-label fw-bold text-black-50">
                        <i class="fas fa-key"></i> Faculty ID
                    </label>
                    <input type="text" class="auth-form-control" id="faculty_verification_code" name="faculty_verification_code"
                        placeholder="Enter your Faculty ID (provided by administrator)" value="<?= htmlspecialchars($_POST['faculty_verification_code'] ?? '') ?>">
                    <small class="text-muted d-block mt-2">
                        <i class="fas fa-info-circle"></i> 
                        If you don't have a Faculty ID, please contact your administrator.
                    </small>
                </div>

                <div class="auth-form-group" id="studentFieldsGroup">
                    <label for="course" class="auth-form-label fw-bold text-black-50">Course</label>
                    <input type="text" class="auth-form-control" id="course" name="course"
                        placeholder="Enter your course" value="<?= htmlspecialchars($_POST['course'] ?? '') ?>">
                </div>

                <div class="auth-form-group" id="departmentFieldsGroup">
                    <label for="department" class="auth-form-label fw-bold text-black-50">Department</label>
                    <input type="text" class="auth-form-control" id="department" name="department"
                        placeholder="Enter your department" value="<?= htmlspecialchars($_POST['department'] ?? '') ?>">
                </div>

                <button type="submit" class="auth-btn-register">Create Account</button>
            </form>

            <div class="auth-links-section">
                <span>Already have an account?</span>
                <a href="login.php" class="auth-link">Login here</a>
            </div>

            <p class="auth-terms-text">
                By creating an account, you consent to ChronoNav's
                <a href="#" class="auth-link" onclick="openAuthTerms()">Terms of Use</a> and
                <a href="#" class="auth-link" onclick="openAuthPrivacy()">Privacy Policy</a>.
            </p>
        </div>

        <div class="auth-footer">
            <p>App Version 1.0.0 · © 2025 ChronoNav</p>
            <p><a href="#">Contact us</a></p>
        </div>
    </div>

    <!-- Privacy Policy Modal -->
    <div class="auth-modal" id="authPrivacyModal">
        <div class="auth-modal-content">
            <div class="auth-modal-header">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div
                        style="background-color: rgba(255, 255, 255, 0.2); border-radius: 8px; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div>
                        <h2 style="margin-bottom: 0.25rem;">Privacy Policy</h2>
                        <p style="margin-bottom: 0; opacity: 0.75;">Last updated: September 30, 2025</p>
                    </div>
                </div>
                <button class="auth-modal-close" onclick="closeAuthPrivacy()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="auth-modal-body">
                <!-- Privacy Policy content would go here -->
                <p>Privacy policy content...</p>
            </div>
            <div class="auth-modal-footer">
                <button class="auth-btn-register" onclick="closeAuthPrivacy()">
                    <i class="fas fa-times me-2"></i> Close
                </button>
            </div>
        </div>
    </div>

    <!-- Terms of Service Modal -->
    <div class="auth-modal" id="authTermsModal">
        <div class="auth-modal-content">
            <div class="auth-modal-header">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div
                        style="background-color: rgba(255, 255, 255, 0.2); border-radius: 8px; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-file-contract"></i>
                    </div>
                    <div>
                        <h2 style="margin-bottom: 0.25rem;">Terms of Service</h2>
                        <p style="margin-bottom: 0; opacity: 0.75;">Last updated: September 30, 2025</p>
                    </div>
                </div>
                <button class="auth-modal-close" onclick="closeAuthTerms()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="auth-modal-body">
                <!-- Terms of Service content would go here -->
                <p class="mb-4">Welcome to <strong>CHRONONAV</strong>! These Terms of Service ("Terms") govern your
                    use
                    of
                    the CHRONONAV mobile and web application. By downloading, accessing, or using CHRONONAV, you
                    agree
                    to
                    comply with these Terms. Please read them carefully.</p>

                <h4 class="section-title">
                    <span class="terms-icon">
                        <i class="fas fa-check-circle"></i>
                    </span>
                    Acceptance of Terms
                </h4>
                <p>By using CHRONONAV, you confirm that you:</p>
                <ul class="custom-list">
                    <li>Are at least 13 years of age (or the minimum required by your institution)</li>
                    <li>Agree to follow these Terms and our Privacy Policy</li>
                    <li>Use CHRONONAV only for lawful academic and personal purposes</li>
                </ul>

                <h4 class="section-title">
                    <span class="terms-icon">
                        <i class="fas fa-list-alt"></i>
                    </span>
                    Description of Service
                </h4>
                <p>CHRONONAV provides:</p>
                <ul class="custom-list">
                    <li>OCR-based schedule import from official study loads</li>
                    <li>Personalized timetable management</li>
                    <li>Campus navigation and turn-by-turn directions</li>
                    <li>Class reminders and alerts</li>
                    <li>Limited offline navigation functionality</li>
                </ul>

                <h4 class="section-title">
                    <span class="terms-icon">
                        <i class="fas fa-user-shield"></i>
                    </span>
                    User Responsibilities
                </h4>
                <p>You agree not to:</p>
                <ul class="custom-list">
                    <li>Upload false or misleading information</li>
                    <li>Share your account credentials with others</li>
                    <li>Use CHRONONAV to engage in cheating, harassment, or illegal activities</li>
                    <li>Tamper with or attempt to hack the system</li>
                </ul>
                <p>You are responsible for ensuring your device is compatible with the app.</p>

                <h4 class="section-title">
                    <span class="terms-icon">
                        <i class="fas fa-database"></i>
                    </span>
                    Data and Privacy
                </h4>
                <p>Our use of your data is described in the <strong>Privacy Policy</strong>. By using CHRONONAV, you
                    consent
                    to the collection and use of your information as outlined there.</p>

                <h4 class="section-title">
                    <span class="terms-icon">
                        <i class="fas fa-check-double"></i>
                    </span>
                    Accuracy of Information
                </h4>
                <p>While CHRONONAV strives to provide accurate schedules and navigation, we do not guarantee that:
                </p>
                <ul class="custom-list">
                    <li>All schedules imported via OCR will be 100% error-free</li>
                    <li>Campus navigation routes will always reflect real-time construction, closures, or events
                    </li>
                </ul>
                <p>Users should verify critical details with their school's official sources.</p>

                <h4 class="section-title">
                    <span class="terms-icon">
                        <i class="fas fa-copyright"></i>
                    </span>
                    Intellectual Property
                </h4>
                <p>All rights, trademarks, and content within CHRONONAV belong to the development team and/or the
                    affiliated
                    university. You may not copy, modify, or redistribute CHRONONAV without permission.</p>

                <h4 class="section-title">
                    <span class="terms-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </span>
                    Limitation of Liability
                </h4>
                <p>CHRONONAV is provided <strong>"as is."</strong> We are not responsible for:</p>
                <ul class="custom-list">
                    <li>Missed classes, delays, or wrong directions caused by inaccurate data</li>
                    <li>Damages caused by reliance on the app in critical situations</li>
                </ul>
                <p>Your use of the app is at your own risk.</p>

                <h4 class="section-title">
                    <span class="terms-icon">
                        <i class="fas fa-sync-alt"></i>
                    </span>
                    Service Modifications
                </h4>
                <p>We reserve the right to update, modify, or discontinue CHRONONAV at any time, with or without
                    notice.
                </p>

                <h4 class="section-title">
                    <span class="terms-icon">
                        <i class="fas fa-ban"></i>
                    </span>
                    Termination
                </h4>
                <p>We may suspend or terminate your access if you violate these Terms. You may also delete your
                    account
                    at
                    any time.</p>

                <h4 class="section-title">
                    <span class="terms-icon">
                        <i class="fas fa-balance-scale"></i>
                    </span>
                    Governing Law
                </h4>
                <p>These Terms shall be governed by the laws of the Republic of the Philippines.</p>

                <h4 class="section-title">
                    <span class="terms-icon">
                        <i class="fas fa-envelope"></i>
                    </span>
                    Contact Us
                </h4>
                <p>If you have questions about these Terms, please contact us:</p>
                <div class="contact-info">
                    <div class="contact-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div>
                        <strong>Email:</strong> support@ChronoNav.com
                    </div>
                </div>
                <div class="contact-info">
                    <div class="contact-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div>
                        <strong>Address:</strong> Sanciangko St, Cebu City, 6000 Cebu
                    </div>
                </div>
            </div>
            <div class="auth-modal-footer">
                <button class="auth-btn-register" onclick="closeAuthTerms()">
                    <i class="fas fa-times me-2"></i> Close
                </button>
            </div>
        </div>
    </div>

    <script>
        // Password visibility toggle
        document.getElementById('togglePassword').addEventListener('click', function () {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });

        // Handle role change to show/hide faculty verification code field
        document.getElementById('role').addEventListener('change', function() {
            const facultyVerificationGroup = document.getElementById('facultyVerificationGroup');
            const facultyVerificationInput = document.getElementById('faculty_verification_code');
            const studentFieldsGroup = document.getElementById('studentFieldsGroup');
            const departmentFieldsGroup = document.getElementById('departmentFieldsGroup');
            const courseInput = document.getElementById('course');
            const departmentInput = document.getElementById('department');
            
            if (this.value === 'faculty') {
                // Show faculty field, hide student fields
                facultyVerificationGroup.style.display = 'block';
                studentFieldsGroup.style.display = 'none';
                departmentFieldsGroup.style.display = 'none';
                facultyVerificationInput.required = true;
                courseInput.required = false;
                departmentInput.required = false;
                courseInput.value = '';
                departmentInput.value = '';
            } else {
                // Show student fields, hide faculty field
                facultyVerificationGroup.style.display = 'none';
                studentFieldsGroup.style.display = 'block';
                departmentFieldsGroup.style.display = 'block';
                facultyVerificationInput.required = false;
                courseInput.required = true;
                departmentInput.required = true;
                facultyVerificationInput.value = '';
            }
        });

        // Show appropriate fields on page load if already selected
        window.addEventListener('load', function() {
            const roleSelect = document.getElementById('role');
            if (roleSelect.value === 'faculty') {
                document.getElementById('facultyVerificationGroup').style.display = 'block';
                document.getElementById('studentFieldsGroup').style.display = 'none';
                document.getElementById('departmentFieldsGroup').style.display = 'none';
                document.getElementById('faculty_verification_code').required = true;
            } else if (roleSelect.value === 'user') {
                document.getElementById('facultyVerificationGroup').style.display = 'none';
                document.getElementById('studentFieldsGroup').style.display = 'block';
                document.getElementById('departmentFieldsGroup').style.display = 'block';
                document.getElementById('course').required = true;
                document.getElementById('department').required = true;
            }
        });

        // Functions for modals
        function openAuthPrivacy() {
            document.getElementById('authPrivacyModal').style.display = 'flex';
            event.preventDefault();
        }

        function closeAuthPrivacy() {
            document.getElementById('authPrivacyModal').style.display = 'none';
        }

        function openAuthTerms() {
            document.getElementById('authTermsModal').style.display = 'flex';
            event.preventDefault();
        }

        function closeAuthTerms() {
            document.getElementById('authTermsModal').style.display = 'none';
        }

        // Close modals when clicking outside
        document.getElementById('authPrivacyModal').addEventListener('click', function (e) {
            if (e.target === this) closeAuthPrivacy();
        });

        document.getElementById('authTermsModal').addEventListener('click', function (e) {
            if (e.target === this) closeAuthTerms();
        });
    </script>
</body>

</html>