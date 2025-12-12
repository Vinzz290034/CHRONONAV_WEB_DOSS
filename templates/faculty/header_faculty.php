<?php
// CHRONONAV_WEB_DOSS/templates/faculty/header_faculty.php
// This file assumes $user (session data), $page_title, and $current_page are set in the including script.

// Initialize variables to avoid 'Undefined variable' warnings if not set by the including page
$display_user_name = htmlspecialchars($display_username ?? ($user['name'] ?? 'Faculty Member'));
$user_role = htmlspecialchars(ucfirst($display_user_role ?? ($user['role'] ?? 'faculty')));

$default_profile_pic_path = '../../uploads/profiles/default-avatar.png'; // Path to a generic default avatar
$profile_pic_src = $default_profile_pic_path; // Default to generic avatar

// The getProfileDropdownData function (from the main page like dashboard or view_profile)
// should set $profile_img_src, $display_username, and $display_user_role.
// If those are not set, fallback to session data or generic defaults.
if (isset($profile_img_src) && !empty($profile_img_src)) {
    $profile_pic_src = $profile_img_src; // Use the path derived from getProfileDropdownData
} else if (isset($user) && is_array($user) && !empty($user['profile_img'])) {
    // Fallback to session data if getProfileDropdownData wasn't used or didn't provide it
    $user_profile_pic_filename = $user['profile_img'];
    $potential_profile_pic_path = '../../' . $user_profile_pic_filename; // Adjust path for header context

    if (file_exists($potential_profile_pic_path) && $user_profile_pic_filename !== 'uploads/profiles/default-avatar.png') {
        $profile_pic_src = $potential_profile_pic_path;
    }
    // Else, it remains default_profile_pic_path
}

// Path to your ChronoNav logos for the navbar and dropdown
$chrononav_main_logo_path = '../../assets/img/chrononav_logo.jpg'; // Main logo for navbar brand
$chrononav_dropdown_logo_path = '../../assets/images/chrononav_logo_small.png'; // Small logo for dropdown header
// Ensure these paths are correct and images exist!
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= $page_title ?? 'ChronoNav - Faculty' ?>
    </title>

    <!-- Font Family -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300..700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Favicon -->
    <link rel="icon" type="image/png"
        href="https://res.cloudinary.com/deua2yipj/image/upload/v1758917007/ChronoNav_logo_muon27.png">

    <script>
        // Check localStorage for dark mode preference and apply immediately
        if (localStorage.getItem('darkMode') === 'enabled') {
            document.body.classList.add('dark-mode');
        }
    </script>
</head>

<body>
    <style>
        :root {
            --primary-dark: #101518;
            --secondary-text: #5c748a;
            --border-color: #e5e7eb;
            --accent-blue: #2e78c6;
            --light-bg: #f9fafb;
            --available-color: #10b981;
            --unavailable-color: #ef4444;

            /* Dark mode variables */
            --dm-bg-primary: #0a0f14;
            --dm-bg-secondary: #121a21;
            --dm-bg-tertiary: #1a2430;
            --dm-text-primary: #e5e8eb;
            --dm-text-secondary: #94a3b8;
            --dm-border-color: #263645;
            --dm-accent-blue: #4a90e2;
            --dm-hover-blue: #1c7dd6;
        }

        .custom-header {
            position: sticky;
        }

        /* Additional styles from the first design - Matching user header exactly */
        .custom-header {
            font-family: "Space Grotesk", "Noto Sans", sans-serif;
            background-color: #fff;
            border-bottom: 1px solid #eaedf1;
            padding: 0.75rem 2.5rem;
            position: sticky;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1030;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        body.dark-mode .custom-header {
            background-color: var(--dm-bg-secondary) !important;
            border-bottom-color: var(--dm-border-color) !important;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2) !important;
        }

        .settings-btn {
            background-color: #eaedf1;
            border: none;
            border-radius: 50%;
            color: #101518;
            width: 40px;
            height: 40px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        body.dark-mode .settings-btn {
            background-color: var(--dm-bg-tertiary) !important;
            color: var(--dm-text-primary) !important;
            border: 1px solid var(--dm-border-color);
        }

        .settings-btn:hover {
            background-color: #d8dce1;
            color: #101518;
            transform: translateY(-2px);
        }

        body.dark-mode .settings-btn:hover {
            background-color: var(--dm-hover-blue) !important;
            color: #ffffff !important;
        }

        /* Profile dropdown specific styles */
        .navbar-profile-dropdown .nav-link {
            padding: 0;
            border: none;
            background: transparent;
        }

        .navbar-profile-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }

        .navbar-profile-dropdown .nav-link:hover .navbar-profile-img {
            border-color: #eaedf1;
            transform: scale(1.05);
        }

        body.dark-mode .navbar-profile-dropdown .nav-link:hover .navbar-profile-img {
            border-color: var(--dm-accent-blue) !important;
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .logo-icon {
            width: 1.5rem;
            height: 1.5rem;
            color: #101518;
            transition: color 0.3s ease;
        }

        body.dark-mode .logo-icon {
            color: var(--dm-text-primary) !important;
        }

        .logo-text {
            font-weight: 700;
            font-size: 1.25rem;
            color: #101518;
            margin-bottom: 0;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        body.dark-mode .logo-text {
            color: var(--dm-text-primary) !important;
        }

        .logo-text:hover {
            color: #101518;
        }

        body.dark-mode .logo-text:hover {
            color: var(--dm-text-primary) !important;
        }

        .header-right-section {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        /* Dropdown menu styling */
        .dropdown-menu {
            border: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-radius: 0.75rem;
            padding: 0.5rem 0;
            transition: all 0.3s ease;
            min-width: 220px;
        }

        body.dark-mode .dropdown-menu {
            background-color: var(--dm-bg-tertiary) !important;
            border: 1px solid var(--dm-border-color) !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3) !important;
        }

        .dropdown-item {
            padding: 0.5rem 1rem;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            color: #333;
        }

        body.dark-mode .dropdown-item {
            color: var(--dm-text-primary) !important;
        }

        .dropdown-item i {
            width: 20px;
            text-align: center;
            margin-right: 0.5rem;
            color: #666;
            transition: color 0.3s ease;
        }

        body.dark-mode .dropdown-item i {
            color: var(--dm-text-secondary) !important;
        }

        .dropdown-item:hover {
            background-color: #f8f9fa;
        }

        body.dark-mode .dropdown-item:hover {
            background-color: var(--dm-hover-blue) !important;
            color: #ffffff !important;
        }

        body.dark-mode .dropdown-item:hover i {
            color: #ffffff !important;
        }

        .dropdown-header {
            font-size: 0.875rem;
            font-weight: 600;
            color: #495057;
            padding: 0.5rem 1rem;
            transition: color 0.3s ease;
        }

        body.dark-mode .dropdown-header {
            color: var(--dm-text-primary) !important;
            background-color: var(--dm-bg-tertiary);
        }

        .dropdown-divider {
            margin: 0.25rem 0;
            border-top: 1px solid #e9ecef;
            transition: border-color 0.3s ease;
        }

        body.dark-mode .dropdown-divider {
            border-top-color: var(--dm-border-color) !important;
        }

        /* Text color adjustments */
        .text-black-50 {
            color: rgba(0, 0, 0, 0.5) !important;
            transition: color 0.3s ease;
        }

        body.dark-mode .text-black-50 {
            color: rgba(255, 255, 255, 0.7) !important;
        }

        /* SVG logo styling */
        .custom-header svg {
            transition: filter 0.3s ease;
        }

        body.dark-mode .custom-header svg {
            filter: brightness(0.9) contrast(1.1);
        }

        /* Navbar toggler */
        .navbar-toggler {
            border: none;
            padding: 0.25rem;
            transition: all 0.3s ease;
        }

        body.dark-mode .navbar-toggler {
            background-color: var(--dm-bg-tertiary);
            color: var(--dm-text-primary);
            display: none;
        }

        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(0, 0, 0, 0.7)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
            transition: background-image 0.3s ease;
            display: none;
        }

        body.dark-mode .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(255, 255, 255, 0.7)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .custom-header {
                padding-left: 1rem;
                padding-right: 1rem;
            }

            .header-right-section {
                gap: 0.75rem;
            }

            .logo-text {
                font-size: 1.1rem;
            }
        }

        @media (max-width: 576px) {
            .header-right-section {
                gap: 0.5rem;
            }

            .settings-btn,
            .navbar-profile-img {
                width: 36px;
                height: 36px;
            }

            .logo-text {
                font-size: 1rem;
            }
        }

        /* Hover effects */
        .custom-header a:not(.dropdown-item):hover {
            transform: translateY(-1px);
            transition: transform 0.3s ease;
        }

        /* Accessibility improvements */
        .settings-btn:focus,
        .navbar-profile-dropdown .nav-link:focus,
        .dropdown-item:focus {
            outline: 2px solid var(--accent-blue);
            outline-offset: 2px;
        }

        body.dark-mode .settings-btn:focus,
        body.dark-mode .navbar-profile-dropdown .nav-link:focus,
        body.dark-mode .dropdown-item:focus {
            outline-color: var(--dm-accent-blue);
        }

        /* Animation for dropdown */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .dropdown-menu.show {
            animation: fadeIn 0.2s ease-out;
        }
    </style>

    <script src="../../assets/js/dark_mode.js" defer></script>

    <!-- Header section using <header> tag -->
    <header class="custom-header shadow-sm">
        <!-- Logo Section -->
        <a href="../../pages/faculty/dashboard.php"
            class="d-flex align-items-center gap-2 link-offset-2 link-underline link-underline-opacity-0">
            <div class="d-flex align-items-center gap-2">
                <div style="width: 2.5rem; height: 2.5rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="100%" height="100%">
                        <image
                            href="https://res.cloudinary.com/deua2yipj/image/upload/v1758917007/ChronoNav_logo_muon27.png"
                            x="0" y="0" width="100" height="100" />
                    </svg>
                </div>
                <h2 class="mb-0 text-black-50 fw-bold fs-5">ChronoNav</h2>
            </div>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
            aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Right Section with Settings and Profile -->
        <div class="header-right-section">

            <!-- Settings Button -->
            <a href="../../pages/faculty/settings.php" class="settings-btn rounded-3" title="Settings">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor"
                    viewBox="0 0 256 256">
                    <path
                        d="M128,80a48,48,0,1,0,48,48A48.05,48.05,0,0,0,128,80Zm0,80a32,32,0,1,1,32-32A32,32,0,0,1,128,160Zm88-29.84q.06-2.16,0-4.32l14.92-18.64a8,8,0,0,0,1.48-7.06,107.21,107.21,0,0,0-10.88-26.25,8,8,0,0,0-6-3.93l-23.72-2.64q-1.48-1.56-3-3L186,40.54a8,8,0,0,0-3.94-6,107.71,107.71,0,0,0-26.25-10.87,8,8,0,0,0-7.06,1.49L130.16,40Q128,40,125.84,40L107.2,25.11a8,8,0,0,0-7.06-1.48A107.6,107.6,0,0,0,73.89,34.51a8,8,0,0,0-3.93,6L67.32,64.27q-1.56,1.49-3,3L40.54,70a8,8,0,0,0-6,3.94,107.71,107.71,0,0,0-10.87,26.25,8,8,0,0,0,1.49,7.06L40,125.84Q40,128,40,130.16L25.11,148.8a8,8,0,0,0-1.48,7.06,107.21,107.21,0,0,0,10.88,26.25,8,8,0,0,0,6,3.93l23.72,2.64q1.49,1.56,3,3L70,215.46a8,8,0,0,0,3.94,6,107.71,107.71,0,0,0,26.25,10.87,8,8,0,0,0,7.06-1.49L125.84,216q2.16.06,4.32,0l18.64,14.92a8,8,0,0,0,7.06,1.48,107.21,107.21,0,0,0,26.25-10.88,8,8,0,0,0,3.93-6l2.64-23.72q1.56-1.48,3-3L215.46,186a8,8,0,0,0,6-3.94,107.71,107.71,0,0,0,10.87-26.25,8,8,0,0,0-1.49-7.06Zm-16.1-6.5a73.93,73.93,0,0,1,0,8.68,8,8,0,0,0,1.74,5.48l14.19,17.73a91.57,91.57,0,0,1-6.23,15L187,173.11a8,8,0,0,0-5.1,2.64,74.11,74.11,0,0,1-6.14,6.14,8,8,0,0,0-2.64,5.1l-2.51,22.58a91.32,91.32,0,0,1-15,6.23l-17.74-14.19a8,8,0,0,0-5-1.75h-.48a73.93,73.93,0,0,1-8.68,0,8,8,0,0,0-5.48,1.74L100.45,215.8a91.57,91.57,0,0,1-15-6.23L82.89,187a8,8,0,0,0-2.64-5.1,74.11,74.11,0,0,1-6.14-6.14,8,8,0,0,0-5.1-2.64L46.43,170.6a91.32,91.32,0,0,1-6.23-15l14.19-17.74a8,8,0,0,0,1.74-5.48,73.93,73.93,0,0,1,0-8.68,8,8,0,0,0-1.74-5.48L40.2,100.45a91.57,91.57,0,0,1,6.23-15L69,82.89a8,8,0,0,0,5.1-2.64,74.11,74.11,0,0,1,6.14-6.14A8,8,0,0,0,82.89,69L85.4,46.43a91.32,91.32,0,0,1,15-6.23l17.74,14.19a8,8,0,0,0,5.48,1.74,73.93,73.93,0,0,1,8.68,0,8,8,0,0,0,5.48-1.74L155.55,40.2a91.57,91.57,0,0,1,15,6.23L173.11,69a8,8,0,0,0,2.64,5.1,74.11,74.11,0,0,1,6.14,6.14,8,8,0,0,0,5.1,2.64l22.58,2.51a91.32,91.32,0,0,1,6.23,15l-14.19,17.74A8,8,0,0,0,199.87,123.66Z" />
                </svg>
            </a>

            <!-- Profile Dropdown - Using the exact same structure as user header -->
            <li class="nav-item dropdown navbar-profile-dropdown" style="list-style: none;">
                <a class="nav-link align-items-center" href="#" id="navbarDropdownMenuLink" role="button"
                    data-bs-toggle="dropdown" aria-expanded="false">
                    <img src="<?= $profile_pic_src ?>" alt="" class="navbar-profile-img"
                        style="width: 40px; height: 40px; object-fit: cover; border-radius: 50%;">
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdownMenuLink">
                    <li>
                        <h6 class="dropdown-header">
                            <?= $display_user_name ?> (
                            <?= $user_role ?>)
                        </h6>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item" href="../../pages/faculty/view_profile.php"><i
                                class="fas fa-user-circle me-2"></i>Profile</a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item" href="../../pages/faculty/support_center.php"><i
                                class="fas fa-question-circle me-2"></i>Support and Ask question</a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item" href="../../pages/faculty/announcements.php"><i
                                class="fas fa-bullhorn me-2"></i>Campus Announcement</a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item" href="../../auth/logout.php"><i
                                class="fas fa-sign-out-alt me-2"></i>Logout</a>
                    </li>
                </ul>
            </li>
        </div>
    </header>

    <script src="../../assets/js/jquery.min.js"></script>
    <script src="../../assets/js/script.js"></script>

    <script>
        // Dark mode detection and adjustment
        function checkDarkMode() {
            if (document.body.classList.contains('dark-mode')) {
                document.body.style.backgroundColor = "var(--dm-bg-primary)";
                document.body.style.color = "var(--dm-text-primary)";

                // Update header specifically
                const header = document.querySelector('.custom-header');
                if (header) {
                    header.style.backgroundColor = "var(--dm-bg-secondary)";
                    header.style.borderBottomColor = "var(--dm-border-color)";
                }
            } else {
                document.body.style.backgroundColor = "#ffffff";
                document.body.style.color = "#333";

                const header = document.querySelector('.custom-header');
                if (header) {
                    header.style.backgroundColor = "#fff";
                    header.style.borderBottomColor = "#eaedf1";
                }
            }
        }

        // Check on load
        document.addEventListener('DOMContentLoaded', function () {
            checkDarkMode();

            // Listen for dark mode changes
            const observer = new MutationObserver(function (mutations) {
                mutations.forEach(function (mutation) {
                    if (mutation.attributeName === 'class') {
                        checkDarkMode();
                    }
                });
            });

            observer.observe(document.body, { attributes: true });
        });

        // Favicon setup
        (function () {
            const faviconUrl = "https://res.cloudinary.com/deua2yipj/image/upload/v1758917007/ChronoNav_logo_muon27.png";
            document.querySelectorAll('link[rel="icon"], link[rel="shortcut icon"]').forEach(link => link.remove());
            const link = document.createElement("link");
            link.rel = "icon";
            link.type = "image/png";
            link.href = faviconUrl;
            document.head.appendChild(link);
        })();
    </script>
</body>

</html>