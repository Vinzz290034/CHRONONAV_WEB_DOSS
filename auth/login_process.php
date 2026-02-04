<?php
// CHRONONAV_WEB_DOSS/auth/login_process.php

// 1. UNIVERSAL SESSION HANDLING (Optimized for Mobile/Web)
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '', 
        'secure' => false, // Set to TRUE if using HTTPS
        'httponly' => true,
        'samesite' => 'Lax' 
    ]);
    session_start();
}

/** @var \mysqli $conn */
// FIXED PATH: Adjusted to '../config/' as per previous directory structure fix
require_once '../config/db_connect.php'; 

// --- NEW: GUEST LOGIN HANDLING ---
// Check if the guest parameter is present in the URL (e.g., login_process.php?guest=true)
if (isset($_GET['guest']) && $_GET['guest'] === 'true') {
    $_SESSION['user'] = [
        'id' => 0, 
        'name' => 'Guest Visitor',
        'role' => 'visitor',
        'profile_img' => 'uploads/profiles/default-avatar.png'
    ];
    $_SESSION['loggedin'] = true;

    // Redirect to the visitor-specific dashboard
    header("Location: ../pages/visitor/dashboard.php");
    exit();
}
// --- END GUEST HANDLING ---

// Standard POST processing for Registered Users
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $_SESSION['message'] = "Please enter both email and password.";
        $_SESSION['message_type'] = "danger";
        header("Location: ../auth/login.php");
        exit();
    }


    $stmt = $conn->prepare("SELECT id, name, email, password, role, is_active FROM users WHERE email = ?");
    if ($stmt) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();


        if ($user) {

            if (password_verify($password, $user['password'])) {

                if ($user['is_active'] == 0) {
                    $_SESSION['message'] = "Your account has been disabled. Please contact support.";
                    $_SESSION['message_type'] = "danger";
                    header("Location: ../auth/login.php");
                    exit();
                }

                // Standard Login Success
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'role' => $user['role']

                ];
                $_SESSION['loggedin'] = true;

                // Dynamic Redirect based on role folder
                $role_folder = (!empty($user['role'])) ? $user['role'] : 'user';
                header("Location: ../pages/{$role_folder}/dashboard.php");
                exit();
            } else {

                $_SESSION['message'] = "Invalid email or password.";
                $_SESSION['message_type'] = "danger";
            }
        } else {

            $_SESSION['message'] = "Invalid email or password.";
            $_SESSION['message_type'] = "danger";
        }
    } else {
        $_SESSION['message'] = "Database error during login.";
        $_SESSION['message_type'] = "danger";
        error_log("Login prepare failed: " . $conn->error);
    }


    header("Location: ../auth/login.php");
    exit();
} else {


    header("Location: ../auth/login.php");
    exit();
}


?>

