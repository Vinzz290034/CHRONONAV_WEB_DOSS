<?php
// CHRONONAV_WEB_DOSS/templates/admin/sidenav_admin.php
// This file assumes $current_page is set in the including script (e.g., pages/admin/dashboard.php)

// Paths are relative to this file's location (templates/admin/)
// So, to go to chrononav_web_doss/, we go up two levels.
$base_path = '../../';

$app_logo_path = $base_path . 'assets/img/chrononav_logo.jpg'; // Assuming your logo is .jpg
$app_name = "ChronoNav";

// The $user variable is typically available from a session check done in the main page
// before including this sidenav. If you need user role for conditional links, ensure $user is passed.
// For example: $user_role = $_SESSION['user']['role'] ?? 'guest';

if (!isset($current_page)) {
    $current_page = '';
}
?>

<!-- <link rel="stylesheet" href="../../assets/css/other_css/sidenav_users.css"> -->

<style>
    /* Sidenav General Styles */
    .app-sidebar {
        font-family: 'Space Grotesk', 'Noto Sans', sans-serif;
        width: 20%;
        background-color: #fff;
        padding: 1rem;
        position: fixed;
        height: 50vh;
        overflow-y: auto;
        z-index: 1000;
        display: flex;
        flex-direction: column;
    }

    /* Sidebar Header (Logo and App Name) */
    .sidebar-header {
        display: flex;
        align-items: center;
        padding: 15px 20px;
        margin-bottom: 20px;
        border-bottom: 1px solid rgba(239, 232, 232, 0.1);
        justify-content: center;
    }

    /* Transparent Scrollbar */
    .app-sidebar::-webkit-scrollbar {
        width: 6px;
    }

    .app-sidebar::-webkit-scrollbar-track {
        background: transparent;
    }

    .app-sidebar::-webkit-scrollbar-thumb {
        background-color: transparent;
        border-radius: 20px;
        border: none;
    }

    /* Firefox support */
    .app-sidebar {
        scrollbar-width: thin;
        scrollbar-color: transparent transparent;
    }

    .logo-container {
        display: flex;
        align-items: center;
        text-decoration: none;
        color: rgba(0, 0, 0, 1);
        font-size: 1.5rem;
        font-weight: 700;
    }

    .app-logo {
        width: 45px;
        color: rgba(0, 0, 0, 1);
        height: 45px;
        object-fit: contain;
        margin-right: 12px;
        border-radius: 5px;
    }

    /* Sidebar Menu - Updated with observed design */
    .app-sidebar-menu {
        flex-grow: 1;
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .app-sidebar-menu .nav-item {
        margin-bottom: 0.25rem;
    }

    .app-sidebar-menu .nav-link {
        display: flex;
        align-items: center;
        padding: 0.5rem 0.75rem;
        color: #111418;
        text-decoration: none;
        font-size: 0.875rem;
        font-weight: 500;
        transition: all 0.3s ease;
        border-radius: 0.75rem;
        gap: 0.75rem;
        margin: 0;
    }

    .app-sidebar-menu .nav-link svg {
        width: 24px;
        height: 24px;
        color: #111418;
        flex-shrink: 0;
    }

    /* Hover State - Updated */
    .app-sidebar-menu .nav-link:hover {
        background-color: #f8f9fa;
        color: #111418;
        transform: none;
        box-shadow: none;
    }

    .app-sidebar-menu .nav-link:hover svg {
        color: #111418;
    }

    /* Active State - Updated */
    .app-sidebar-menu .nav-link.active {
        background-color: #f0f2f5;
        color: #111418;
        font-weight: 500;
        box-shadow: none;
        transform: none;
    }

    .app-sidebar-menu .nav-link.active svg {
        color: #111418;
    }

    .nav-link-text {
        color: inherit;
        font-size: 0.875rem;
        font-weight: 500;
        line-height: 1.25;
        margin: 0;
    }

    /* Specific for Logout button at the bottom */
    .app-sidebar .nav-item.mt-auto {
        margin-top: auto;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        padding-top: 15px;
        margin-top: 20px;
    }

    .app-sidebar .nav-item.mt-auto .nav-link {
        color: #e74c3c;
        font-weight: 600;
    }

    .app-sidebar .nav-item.mt-auto .nav-link svg {
        color: #e74c3c;
    }

    .app-sidebar .nav-item.mt-auto .nav-link:hover {
        background-color: #c0392b;
        color: #ffffff;
    }

    .app-sidebar .nav-item.mt-auto .nav-link:hover svg {
        color: #ffffff;
    }

    /* Mobile and Tablet: 1023px and below (New Logic) */
    @media (max-width: 1023px) {

        /* Default state: Collapsed (Icons only, pushed slightly off-screen) */
        .app-sidebar {
            width: 60px;
            /* Collapsed width */
            /* Use transform to move it off-screen, but visible enough for a toggle */
            transform: translateX(-100%);
            padding: 0.5rem;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        /* State when sidebar is active/open */
        .app-sidebar.active {
            width: 200px;
            /* Expanded width on mobile/tablet */
            transform: translateX(0);
            /* Slide into view */
        }

        /* Default collapsed appearance (Icons only) */
        .sidebar-header {
            padding: 10px 5px;
            justify-content: center;
        }

        .app-name {
            display: none;
        }

        .app-logo {
            margin-right: 0;
        }

        .app-sidebar-menu .nav-link {
            padding: 0.75rem;
            justify-content: center;
            margin: 0;
        }

        .app-sidebar-menu .nav-link .nav-link-text {
            display: none;
        }

        .app-version {
            display: none;
        }

        /* Expanded State inside the media query, applied via JS */
        .app-sidebar.active .sidebar-header {
            justify-content: flex-start;
            padding: 15px 20px;
        }

        .app-sidebar.active .app-name {
            display: block;
        }

        .app-sidebar.active .app-logo {
            margin-right: 12px;
        }

        .app-sidebar.active .app-sidebar-menu .nav-link {
            padding: 0.5rem 0.75rem;
            justify-content: flex-start;
        }

        .app-sidebar.active .app-sidebar-menu .nav-link .nav-link-text {
            display: block;
        }

        .app-sidebar.active .app-version {
            display: block;
        }
    }

    /* Desktop: 1024px and above (Default Desktop View) */
    @media (min-width: 1024px) {
        .app-sidebar {
            width: 20%;
            padding: 1rem;
            transform: none;
            /* Ensure no transformation on desktop */
        }

        .sidebar-header {
            padding: 15px 20px;
            justify-content: flex-start;
        }

        .app-name {
            display: block;
        }

        .app-logo {
            margin-right: 12px;
        }

        .app-sidebar-menu .nav-link {
            padding: 0.5rem 0.75rem;
            justify-content: flex-start;
        }

        .app-sidebar-menu .nav-link .nav-link-text {
            display: block;
        }

        .app-version {
            display: block;
        }
    }

    /* Basic styling for the main content area to make space for the sidebar */
    .main-content-wrapper {
        margin-left: 20%;
        transition: margin-left 0.3s ease;
        padding: 2rem;
        min-height: 100vh;
    }

    /* Adjust main content for smaller screens: It should have NO margin-left */
    @media (max-width: 1023px) {
        .main-content-wrapper {
            /* Remove margin-left on smaller screens so the sidebar overlays it */
            margin-left: 0;
        }
    }

    /* Toggle button for mobile/tablet */
    .sidebar-toggle {
        display: none;
        position: fixed;
        top: 15px;
        left: 15px;
        z-index: 1100;
        background: #3e99f4;
        color: white;
        border: none;
        border-radius: 5px;
        padding: 8px 12px;
        font-size: 1.2rem;
    }

    @media (max-width: 1023px) {
        .sidebar-toggle {
            display: block;
        }
    }


    /* button for the collapsed sidebar on mobile/tablet */
    .sidebar-toggle {
        display: none;
        /* Hidden by default */
        position: fixed;
        /* Positioned for mobile/tablet */
        left: 0;
        top: 15px;
        /* Vertical position near the top */
        z-index: 1100;
        /* Ensure it is above the sidebar */

        /* Arrow Shape Styling */
        width: 30px;
        height: 30px;
        background: #f0f2f5;
        color: #111418;
        border: 1px solid #ddd;
        border-radius: 50%;
        /* Circular shape */
        cursor: pointer;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease, background-color 0.3s ease, left 0.3s ease;

        /* Align icon perfectly */
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .sidebar-toggle:hover {
        background-color: #e0e2e5;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
    }

    .sidebar-toggle svg {
        color: #111418;
        transition: transform 0.3s ease;
    }

    /* Rotate icon when sidebar is expanded (it should point left, representing 'collapse') */
    .sidebar-toggle.rotated svg {
        transform: rotate(180deg);
    }

    /* -------------------- Mobile/Tablet: 1023px and below -------------------- */
    @media (max-width: 1023px) {
        .app-sidebar {
            width: 200px;
            /* Expanded width on mobile/tablet */
            /* Default state: Collapsed (hidden off-screen) */
            transform: translateX(-100%);
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
            /* Shadow when open */
            padding: 1rem;
            transition: transform 0.3s ease;
        }

        /* Active state: Slides into view */
        .app-sidebar.active {
            transform: translateX(0);
        }

        /* Show the toggle button */
        .sidebar-toggle {
            display: flex;
            position: fixed;
            /* CHANGE: Set 'right: 5px' to position the button on the right edge */
            right: 1rem;
            /* CHANGE: Set 'left: unset' to remove the default left positioning */
            left: unset;
            top: 5rem;
            z-index: 1100;

            /* Arrow Shape Styling (UNCHANGED) */
            width: 30px;
            height: 30px;
            background: #f0f2f5;
            color: #111418;
            border: 1px solid #ddd;
            border-radius: 50%;
            cursor: pointer;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, background-color 0.3s ease, right 0.3s ease;

            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Sidebar Text and Elements are visible in the expanded state */
        .sidebar-header {
            justify-content: flex-start;
            padding: 15px 20px;
        }

        .app-name,
        .app-version {
            display: block !important;
        }

        .app-logo {
            margin-right: 12px;
        }

        .app-sidebar-menu .nav-link {
            padding: 0.5rem 0.75rem;
            justify-content: flex-start;
        }

        .app-sidebar-menu .nav-link .nav-link-text {
            display: block;
        }

        /* Main content must have NO margin-left on small screens (overlay behavior) */
        .main-content-wrapper {
            margin-left: 0 !important;
        }
    }


    /* -------------------- Desktop: 1024px and above (Static view) -------------------- */
    @media (min-width: 1024px) {
        .app-sidebar {
            width: 20%;
            padding: 1rem;
            transform: none;
            /* Ensure no transformation */
        }

        .main-content-wrapper {
            margin-left: 20%;
            /* Push content on desktop */
        }

        .sidebar-toggle {
            display: none;
            /* Hide the button */
        }

        /* Ensure all text elements are visible on desktop */
        .app-name,
        .app-version,
        .nav-link-text {
            display: block;
        }

    }

    /* ====================================================================== */
    /* Dark Mode Overrides for Admin Sidenav - Updated Color Scheme           */
    /* ====================================================================== */
    body.dark-mode .app-sidebar {
        background-color: #121A21 !important;
        border-right: 1px solid #121A21 !important;
        color: #E5E8EB !important;
    }

    body.dark-mode .sidebar-header {
        border-bottom: 1px solid #263645 !important;
    }

    body.dark-mode .logo-container {
        color: #E5E8EB !important;
    }

    body.dark-mode .app-logo {
        color: #E5E8EB !important;
        filter: brightness(1.1) !important;
    }

    body.dark-mode .app-sidebar-menu .nav-link {
        color: #94ADC7 !important;
    }

    body.dark-mode .app-sidebar-menu .nav-link svg {
        color: #94ADC7 !important;
    }

    body.dark-mode .app-sidebar-menu .nav-link:hover {
        background-color: #263645 !important;
        color: #E5E8EB !important;
    }

    body.dark-mode .app-sidebar-menu .nav-link:hover svg {
        color: #E5E8EB !important;
    }

    body.dark-mode .app-sidebar-menu .nav-link.active {
        background-color: #1C7DD6 !important;
        color: #FFFFFF !important;
        font-weight: 600 !important;
    }

    body.dark-mode .app-sidebar-menu .nav-link.active svg {
        color: #FFFFFF !important;
    }

    body.dark-mode .nav-link-text {
        color: inherit !important;
    }

    body.dark-mode .app-sidebar .nav-item.mt-auto {
        border-top: 1px solid #263645 !important;
    }

    body.dark-mode .app-sidebar .nav-item.mt-auto .nav-link {
        color: #94ADC7 !important;
    }

    body.dark-mode .app-sidebar .nav-item.mt-auto .nav-link svg {
        color: #94ADC7 !important;
    }

    body.dark-mode .app-sidebar .nav-item.mt-auto .nav-link:hover {
        background-color: #1C7DD6 !important;
        color: #FFFFFF !important;
    }

    body.dark-mode .app-sidebar .nav-item.mt-auto .nav-link:hover svg {
        color: #FFFFFF !important;
    }

    body.dark-mode .app-version .text-muted {
        color: #94ADC7 !important;
    }

    /* Toggle button for dark mode */
    body.dark-mode .sidebar-toggle {
        background: #263645 !important;
        color: #E5E8EB !important;
        border: 1px solid #121A21 !important;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3) !important;
    }

    body.dark-mode .sidebar-toggle:hover {
        background-color: #1C7DD6 !important;
        color: #FFFFFF !important;
    }

    body.dark-mode .sidebar-toggle svg {
        color: #94ADC7 !important;
    }

    body.dark-mode .sidebar-toggle:hover svg {
        color: #FFFFFF !important;
    }

    body.dark-mode .sidebar-toggle.rotated svg {
        color: #94ADC7 !important;
    }

    /* Scrollbar for dark mode sidenav */
    body.dark-mode .app-sidebar::-webkit-scrollbar-track {
        background: #121A21 !important;
    }

    body.dark-mode .app-sidebar::-webkit-scrollbar-thumb {
        background-color: #263645 !important;
        border-radius: 20px !important;
    }

    body.dark-mode .app-sidebar::-webkit-scrollbar-thumb:hover {
        background-color: #1C7DD6 !important;
    }

    /* Firefox support for dark mode */
    body.dark-mode .app-sidebar {
        scrollbar-color: #263645 #121A21 !important;
    }

    /* Main content adjustments for dark mode */
    body.dark-mode .main-content-wrapper {
        background-color: #121A21 !important;
        color: #E5E8EB !important;
    }

    /* Media query adjustments for dark mode */
    @media (max-width: 1023px) {
        body.dark-mode .app-sidebar {
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.4) !important;
            border-right: 1px solid #121A21 !important;
        }

        body.dark-mode .app-sidebar.active {
            background-color: #121A21 !important;
        }

        body.dark-mode .sidebar-toggle {
            background: #263645 !important;
            border: 1px solid #121A21 !important;
        }
    }

    /* Demo content styles for dark mode */
    body.dark-mode .demo-content {
        background: #263645 !important;
        color: #E5E8EB !important;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3) !important;
    }

    body.dark-mode .screen-size-indicator {
        background: #1C7DD6 !important;
        color: #FFFFFF !important;
    }

    /* Active state enhancements for dark mode */
    body.dark-mode .app-sidebar-menu .nav-link.active::before {
        background-color: #1C7DD6 !important;
    }

    /* Hover effects enhancement for dark mode */
    body.dark-mode .app-sidebar-menu .nav-link:hover {
        box-shadow: 0 2px 8px rgba(28, 125, 214, 0.2) !important;
    }

    /* Version text styling for dark mode */
    body.dark-mode .app-version {
        border-top: 1px solid #263645 !important;
    }

    /* Desktop specific dark mode adjustments */
    @media (min-width: 1024px) {
        body.dark-mode .app-sidebar {
            background-color: #121A21 !important;
            border-right: 1px solid #121A21 !important;
        }

        body.dark-mode .main-content-wrapper {
            background-color: #121A21 !important;
        }
    }

    /* Focus states for accessibility in dark mode */
    body.dark-mode .app-sidebar-menu .nav-link:focus {
        outline: 2px solid #1C7DD6 !important;
        outline-offset: 2px !important;
    }

    /* Transition effects for smooth dark mode changes */
    body.dark-mode .app-sidebar,
    body.dark-mode .app-sidebar-menu .nav-link,
    body.dark-mode .sidebar-toggle {
        transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease !important;
    }

    /* Override the existing dark mode styles to use the new color scheme */
    body.dark-mode .app-sidebar {
        background-color: #121A21 !important;
        border-right: 1px solid #121A21 !important;
    }

    body.dark-mode .sidebar-header {
        border-bottom: 1px solid #263645 !important;
    }

    body.dark-mode .app-sidebar-menu .nav-link {
        color: #94ADC7 !important;
    }

    body.dark-mode .app-sidebar-menu .nav-link svg {
        color: #94ADC7 !important;
    }

    body.dark-mode .app-sidebar-menu .nav-link:hover {
        background-color: #263645 !important;
        color: #E5E8EB !important;
    }

    body.dark-mode .app-sidebar-menu .nav-link.active {
        background-color: #1C7DD6 !important;
        color: #FFFFFF !important;
    }

    body.dark-mode .app-sidebar .nav-item.mt-auto {
        border-top: 1px solid #263645 !important;
    }

    /* Demo content styles */
    body {
        font-family: 'Space Grotesk', 'Noto Sans', sans-serif;
        background-color: #f8f9fa;
        margin: 0;
        padding: 0;
    }

    .demo-content {
        background: white;
        border-radius: 8px;
        padding: 2rem;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .screen-size-indicator {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: #333;
        color: white;
        padding: 10px 15px;
        border-radius: 5px;
        font-size: 0.9rem;
    }
</style>

<button id="sidebarToggle"
    class="sidebar-toggle d-md-none top-7 start-5 z-5 bg-body text-dark border-0 rounded-3 p-1 fs-5">
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 256 256">
        <path
            d="M40,128a8,8,0,0,1,8-8H208a8,8,0,0,1,0,16H48A8,8,0,0,1,40,128ZM48,64H208a8,8,0,0,1,0,16H48A8,8,0,0,1,48,64ZM208,184H48a8,8,0,0,0,0,16H208a8,8,0,0,0,0-16Z">
        </path>
    </svg>
</button>

<div class="app-sidebar overflow-y-hidden" id="sidebar">
    <div class="app-sidebar-menu">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= ($current_page === 'dashboard') ? 'active' : '' ?>"
                    href="<?= $base_path ?>pages/admin/dashboard.php">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" fill="currentColor"
                        viewBox="0 0 256 256">
                        <path
                            d="M218.83,103.77l-80-75.48a1.14,1.14,0,0,1-.11-.11,16,16,0,0,0-21.53,0l-.11.11L37.17,103.77A16,16,0,0,0,32,115.55V208a16,16,0,0,0,16,16H96a16,16,0,0,0,16-16V160h32v48a16,16,0,0,0,16,16h48a16,16,0,0,0,16-16V115.55A16,16,0,0,0,218.83,103.77ZM208,208H160V160a16,16,0,0,0-16-16H112a16,16,0,0,0-16,16v48H48V115.55l.11-.1L128,40l79.9,75.43.11.1Z">
                        </path>
                    </svg>
                    <span class="nav-link-text fs-6">Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($current_page === 'map_management') ? 'active' : '' ?>"
                    href="<?= $base_path ?>pages/admin/map_management.php">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" transform="scale(0.8)"
                        fill="currentColor" class="bi bi-map" viewBox="0 0 16 16">
                        <path
                            d="M15.817.113A.5.5 0 0 1 16 .5v14a.5.5 0 0 1-.402.49l-5 1a.5.5 0 0 1-.196 0L5.5 15.01l-4.902.98A.5.5 0 0 1 0 15.5v-14a.5.5 0 0 1 .402-.49l5-1a.5.5 0 0 1 .196 0L10.5.99l4.902-.98a.5.5 0 0 1 .415.103M10 1.91l-4-.8v12.98l4 .8zm1 12.98 4-.8V1.11l-4 .8zm-6-.8V1.11l-4 .8v12.96z" />
                    </svg>
                    <span class="nav-link-text fs-6">Navigate Map</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($current_page === 'schedule') ? 'active' : '' ?>"
                    href="<?= $base_path ?>pages/admin/schedule.php">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" fill="currentColor"
                        viewBox="0 0 256 256">
                        <path
                            d="M208,32H184V24a8,8,0,0,0-16,0v8H88V24a8,8,0,0,0-16,0v8H48A16,16,0,0,0,32,48V208a16,16,0,0,0,16,16H208a16,16,0,0,0,16-16V48A16,16,0,0,0,208,32ZM72,48v8a8,8,0,0,0,16,0V48h80v8a8,8,0,0,0,16,0V48h24V80H48V48ZM208,208H48V96H208V208Zm-96-88v64a8,8,0,0,1-16,0V132.94l-4.42,2.22a8,8,0,0,1-7.16-14.32l16-8A8,8,0,0,1,112,120Zm59.16,30.45L152,176h16a8,8,0,0,1,0,16H136a8,8,0,0,1-6.4-12.8l28.78-38.37A8,8,0,1,0,145.07,132a8,8,0,1,1-13.85-8A24,24,0,0,1,176,136,23.76,23.76,0,0,1,171.16,150.45Z">
                        </path>
                    </svg>
                    <span class="nav-link-text fs-6">Schedule</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($current_page === 'report_generator') ? 'active' : '' ?>"
                    href="<?= $base_path ?>pages/admin/report_generator.php">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" fill="currentColor"
                        viewBox="0 0 256 256">
                        <path
                            d="M216,40H40A16,16,0,0,0,24,56V200a16,16,0,0,0,16,16H216a16,16,0,0,0,16-16V56A16,16,0,0,0,216,40ZM40,56H216V88H40ZM216,200H40V104H216v96Zm-24-64a8,8,0,0,1-8,8H96a8,8,0,0,1,0-16h88A8,8,0,0,1,192,136Zm0,32a8,8,0,0,1-8,8H96a8,8,0,0,1,0-16h88A8,8,0,0,1,192,168Z">
                        </path>
                    </svg>
                    <span class="nav-link-text fs-6">Report Generator</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($current_page === 'faculty_verification_codes') ? 'active' : '' ?>"
                    href="<?= $base_path ?>pages/admin/faculty_verification_codes.php">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" transform="scale(0.8)"
                        fill="currentColor" class="bi bi-code-square" viewBox="0 0 16 16">
                        <path
                            d="M14 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1zM2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2z" />
                        <path
                            d="M6.854 4.646a.5.5 0 0 1 0 .708L4.207 8l2.647 2.646a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 0 1 .708 0m2.292 0a.5.5 0 0 0 0 .708L11.793 8l-2.647 2.646a.5.5 0 0 0 .708.708l3-3a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708 0" />
                    </svg>
                    <span class="nav-link-text fs-6">Faculty Codes</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($current_page === 'manage_admins') ? 'active' : '' ?>"
                    href="<?= $base_path ?>pages/admin/manage_admins.php">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" transform="scale(0.8)"
                        fill="currentColor" class="bi bi-kanban" viewBox="0 0 16 16">
                        <path
                            d="M13.5 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1h-11a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1zm-11-1a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2z" />
                        <path
                            d="M6.5 3a1 1 0 0 1 1-1h1a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1a1 1 0 0 1-1-1zm-4 0a1 1 0 0 1 1-1h1a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1h-1a1 1 0 0 1-1-1zm8 0a1 1 0 0 1 1-1h1a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1h-1a1 1 0 0 1-1-1z" />
                    </svg>
                    <span class="nav-link-text fs-6">Admin Management</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($current_page === 'profile') ? 'active' : '' ?>"
                    href="<?= $base_path ?>pages/admin/view_profile.php">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" fill="currentColor"
                        viewBox="0 0 256 256">
                        <path
                            d="M230.92,212c-15.23-26.33-38.7-45.21-66.09-54.16a72,72,0,1,0-73.66,0C63.78,166.78,40.31,185.66,25.08,212a8,8,0,1,0,13.85,8c18.84-32.56,52.14-52,89.07-52s70.23,19.44,89.07,52a8,8,0,1,0,13.85-8ZM72,96a56,56,0,1,1,56,56A56.06,56.06,0,0,1,72,96Z">
                        </path>
                    </svg>
                    <span class="nav-link-text fs-6">Profile</span>
                </a>
            </li>
        </ul>
    </div>
    <div class="app-version text-left py-3 m-3 fw-bold">
        <p class="text-muted mb-0">ChronoNav v1.0</p>
    </div>


    <script>
        // JavaScript for sidebar functionality
        document.addEventListener('DOMContentLoaded', function () {
            const sidebar = document.getElementById('sidebar');
            // You need to ensure the sidebarToggle button exists in your HTML structure 
            // if it's not present in the provided snippet (it's only in the CSS/JS).
            // Assuming it's in the main layout or wrapper.
            const sidebarToggle = document.getElementById('sidebarToggle');
            const toggleIcon = document.getElementById('toggleIcon');
            const mainContent = document.querySelector('.main-content-wrapper');
            const screenSizeText = document.getElementById('screenSizeText');

            // Function to update screen size indicator (UNCHANGED)
            function updateScreenSizeIndicator() {
                const width = window.innerWidth;
                if (width <= 767) {
                    screenSizeText.textContent = 'Mobile';
                } else if (width >= 768 && width <= 1023) {
                    screenSizeText.textContent = 'Tablet';
                } else {
                    screenSizeText.textContent = 'Desktop';
                }
            }

            // Function to check if we are on a mobile/tablet view
            function isSmallScreen() {
                return window.innerWidth < 1024;
            }

            // Toggle sidebar on button click
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function () {
                    // Only toggle the active class on small screens
                    if (isSmallScreen()) {
                        sidebar.classList.toggle('active');
                        // Toggle the rotated class on the button itself (CSS handles the rotation)
                        sidebarToggle.classList.toggle('rotated');
                    }
                });
            }


            // Handle window resize: Reset sidebar style for desktop view
            window.addEventListener('resize', function () {
                updateScreenSizeIndicator();

                if (!isSmallScreen()) {
                    // Desktop view: Ensure it is fully open, remove 'active' if present
                    sidebar.style.width = '20%';
                    mainContent.style.marginLeft = '20%';
                    sidebar.classList.remove('active');

                    // Clear any inline styles that might conflict with media query (especially transform)
                    sidebar.style.transform = '';

                } else {
                    // Mobile/Tablet view: Ensure main content is not pushed, and sidebar is collapsed by default
                    mainContent.style.marginLeft = '0';
                    // Reset to collapsed width (60px) if not active
                    if (!sidebar.classList.contains('active')) {
                        sidebar.style.width = ''; // Let CSS media query handle the default 60px/transform
                    } else {
                        // If it was already active, keep the expanded width
                        sidebar.style.width = '200px';
                    }
                }
            });

            // Initial setup for small screens
            function initializeSidebar() {
                updateScreenSizeIndicator();
                if (isSmallScreen()) {
                    // Ensure main content is NOT pushed on small screens on load
                    mainContent.style.marginLeft = '0';
                    // Remove the 'active' class on load for mobile/tablet to ensure it's collapsed
                    sidebar.classList.remove('active');
                } else {
                    // Ensure correct desktop state on load
                    sidebar.style.width = '20%';
                    mainContent.style.marginLeft = '20%';
                }
            }

            // Initialize
            initializeSidebar();
        });
    </script>
</div>