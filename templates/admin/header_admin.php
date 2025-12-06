<?php
// CHRONONAV_WEB_DOSS/templates/admin/header_admin.php
// This file assumes $user (session data), $page_title, and $current_page are set in the including script.

// Initialize variables to avoid 'Undefined variable' warnings if not set by the including page
$display_user_name = htmlspecialchars($display_username ?? ($user['name'] ?? 'Admin User'));
$user_role = htmlspecialchars(ucfirst($display_user_role ?? ($user['role'] ?? 'admin')));

$default_profile_pic_path = '../../uploads/profiles/default-avatar.png'; // Path to a generic default avatar
$profile_pic_src = $default_profile_pic_path; // Default to generic avatar

// Handle profile image from session or function like getProfileDropdownData
if (isset($profile_img_src) && !empty($profile_img_src)) {
    $profile_pic_src = $profile_img_src;
} else if (isset($user) && is_array($user) && !empty($user['profile_img'])) {
    $user_profile_pic_filename = $user['profile_img'];
    $potential_profile_pic_path = '../../' . $user_profile_pic_filename;

    if (file_exists($potential_profile_pic_path) && $user_profile_pic_filename !== 'uploads/profiles/default-avatar.png') {
        $profile_pic_src = $potential_profile_pic_path;
    }
}

// Path to ChronoNav logos
$chrononav_main_logo_path = '../../assets/img/chrononav_logo.jpg';
$chrononav_dropdown_logo_path = '../../assets/images/chrononav_logo_small.png';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= $page_title ?? 'ChronoNav - Admin' ?>
    </title>

    <!-- Font Family -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300..700&display=swap" rel="stylesheet">
    <!-- important------------------------------------------------------------------------------------------------ -->

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Favicon -->
    <link rel="icon" type="image/png"
        href="https://res.cloudinary.com/deua2yipj/image/upload/v1758917007/ChronoNav_logo_muon27.png">

    <!-- <link rel="stylesheet" href="../../assets/css/admin_css/header_admin.css"> -->

    <script>
        // Check localStorage for dark mode preference and apply immediately
        if (localStorage.getItem('darkMode') === 'enabled') {
            document.body.classList.add('dark-mode');
        }
    </script>
</head>

<body>
    <style>
        .custom-header {
            position: sticky;
        }

        /* ====================================================================== */
        /* Dark Mode Overrides for Header - Custom Colors                          */
        /* ====================================================================== */
        body.dark-mode .custom-header {
            background-color: #121A21 !important;
            /* Primary dark background */
            border-bottom: 1px solid #263645 !important;
            /* Dark border */
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3) !important;
        }

        body.dark-mode .custom-header h2 {
            color: #E5E8EB !important;
            /* Light text color */
        }

        body.dark-mode .custom-header .link-offset-2:hover h2 {
            color: #FFFFFF !important;
            /* White on hover */
        }

        /* Settings button in dark mode */
        body.dark-mode .settings-btn {
            background-color: #263645 !important;
            /* Secondary dark */
            color: #94ADC7 !important;
            /* Secondary text color */
            border: 1px solid #121A21 !important;
        }

        body.dark-mode .settings-btn:hover {
            background-color: #1C7DD6 !important;
            /* Active blue */
            color: #FFFFFF !important;
            /* White on hover */
        }

        body.dark-mode .settings-btn svg {
            color: #94ADC7 !important;
            /* Icon color */
        }

        body.dark-mode .settings-btn:hover svg {
            color: #FFFFFF !important;
            /* White icon on hover */
        }

        /* Profile dropdown in dark mode */
        body.dark-mode .navbar-profile-img {
            border: 2px solid #263645 !important;
            box-shadow: 0 0 0 2px #1C7DD6 !important;
            /* Blue outline on active */
        }

        body.dark-mode .dropdown-menu {
            background-color: #121A21 !important;
            /* Primary dark background */
            border: 1px solid #263645 !important;
            /* Dark border */
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4) !important;
        }

        body.dark-mode .dropdown-menu .text-dark {
            color: #E5E8EB !important;
            /* Light text for username */
        }

        body.dark-mode .dropdown-menu .text-muted {
            color: #94ADC7 !important;
            /* Secondary color for role */
        }

        body.dark-mode .dropdown-divider {
            border-color: #263645 !important;
            /* Dark divider */
        }

        body.dark-mode .dropdown-item {
            color: #94ADC7 !important;
            /* Secondary text color */
            background-color: transparent !important;
        }

        body.dark-mode .dropdown-item:hover {
            background-color: #263645 !important;
            /* Dark hover background */
            color: #FFFFFF !important;
            /* White text on hover */
        }

        body.dark-mode .dropdown-item:active {
            background-color: #1C7DD6 !important;
            /* Active blue */
            color: #FFFFFF !important;
        }

        /* Dropdown icons in dark mode */
        body.dark-mode .dropdown-item .text-primary {
            color: #1C7DD6 !important;
            /* Blue for profile icon */
        }

        body.dark-mode .dropdown-item .text-info {
            color: #94ADC7 !important;
            /* Secondary color for announcements */
        }

        body.dark-mode .dropdown-item .text-secondary {
            color: #94ADC7 !important;
            /* Secondary color for support */
        }

        body.dark-mode .dropdown-item:hover .text-primary,
        body.dark-mode .dropdown-item:hover .text-info,
        body.dark-mode .dropdown-item:hover .text-secondary {
            color: #FFFFFF !important;
            /* White icons on hover */
        }

        /* Logout item in dark mode */
        body.dark-mode .dropdown-item.text-danger {
            color: #E57373 !important;
            /* Slightly lighter red for dark mode */
        }

        body.dark-mode .dropdown-item.text-danger:hover {
            background-color: #C62828 !important;
            /* Darker red background on hover */
            color: #FFFFFF !important;
        }

        /* Dropdown arrow in dark mode */
        body.dark-mode .navbar-profile-dropdown .nav-link::after {
            border-color: #94ADC7 !important;
            /* Secondary color for dropdown arrow */
        }

        body.dark-mode .navbar-profile-dropdown .nav-link:hover::after {
            border-color: #FFFFFF !important;
            /* White arrow on hover */
        }

        /* Body background in dark mode */
        body.dark-mode {
            background-color: #121A21 !important;
            color: #E5E8EB !important;
        }

        /* Logo SVG adjustments for dark mode */
        body.dark-mode .custom-header svg image {
            filter: brightness(1.1);
            /* Slightly brighten logo for dark mode */
        }

        /* Main header styling - matching user header exactly */
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
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .settings-btn:hover {
            background-color: #d8dce1;
            color: #101518;
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
            transition: border-color 0.2s ease;
        }

        .navbar-profile-dropdown .nav-link:hover .navbar-profile-img {
            border-color: #eaedf1;
        }

        .header-right-section {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        /* Dropdown menu styling - matching user header exactly */
        .dropdown-menu {
            border: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-radius: 0.75rem;
            padding: 0.5rem 0;
            min-width: 220px;
        }

        .dropdown-item {
            padding: 0.5rem 1rem;
            display: flex;
            align-items: center;
            font-size: 14px;
        }

        .dropdown-item i {
            width: 20px;
            text-align: center;
            margin-right: 0.75rem;
        }

        /* Updated dropdown structure to match user header */
        .dropdown-header {
            font-weight: 600;
            font-size: 16px;
            color: #101518;
            padding: 0.5rem 1rem;
            background: transparent;
            border-bottom: none;
        }

        .dropdown-divider {
            margin: 0.25rem 0;
            border-color: #e5e7eb;
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
        }

        @media (max-width: 576px) {
            .header-right-section {
                gap: 0.5rem;
            }
        }

        /* Remove navbar-toggler button styling */
        .navbar-toggler {
            display: none;
        }

        /* Link styling */
        a {
            text-decoration: none;
            color: inherit;
        }

        a:hover {
            color: inherit;
        }
    </style>

    <script src="../../assets/js/dark_mode.js" defer></script>

    <!-- Header section using <header> tag -->
    <header class="custom-header shadow-sm">
        <!-- Logo Section -->
        <a href="../../pages/admin/dashboard.php"
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

        <!-- Right Section with Settings and Profile -->
        <div class="header-right-section">

            <!-- Settings Button -->
            <a href="../../pages/admin/settings.php" class="settings-btn rounded-3" title="Settings">
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
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 p-2"
                    aria-labelledby="navbarDropdownMenuLink">

                    <li class="px-3 py-2">
                        <div class="d-flex flex-column">
                            <strong class="text-dark fs-6"><?= $display_user_name ?></strong>
                            <small class="text-muted fw-light"><?= $user_role ?></small>
                        </div>
                    </li>

                    <li>
                        <hr class="dropdown-divider my-2">
                    </li>

                    <li>
                        <a class="dropdown-item rounded-2" href="../../pages/admin/view_profile.php">
                            <i class="fas fa-user-circle me-3 fa-fw text-primary"></i>Profile
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item rounded-2" href="../../pages/admin/announcements.php">
                            <i class="fas fa-bullhorn me-3 fa-fw text-info"></i>Announcements
                        </a>
                    </li>

                    <li>
                        <hr class="dropdown-divider my-2">
                    </li>

                    <li>
                        <a class="dropdown-item rounded-2" href="../../pages/admin/support_center.php">
                            <i class="fas fa-question-circle me-3 fa-fw text-secondary"></i>Support & Help
                        </a>
                    </li>

                    <li>
                        <hr class="dropdown-divider my-2">
                    </li>

                    <li>
                        <a class="dropdown-item rounded-2 text-danger" href="../../auth/logout.php">
                            <i class="fas fa-sign-out-alt me-3 fa-fw"></i>Logout
                        </a>
                    </li>
                </ul>
            </li>
        </div>
    </header>

    <script src="../../assets/js/jquery.min.js"></script>
    <script src="../../assets/js/script.js"></script>
</body>

</html>