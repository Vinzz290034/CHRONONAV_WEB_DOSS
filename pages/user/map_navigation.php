<?php
// CHRONONAV_WEB_DOSS/pages/user/map_navigation.php
// Map navigation page for students

require_once '../../middleware/auth_check.php';
require_once '../../includes/db_connect.php';
require_once('../../templates/footer.php');
$user = $_SESSION['user'];
$page_title = "Campus Map - Navigation";
$current_page = "map";

// Define the available floors and their corresponding SVG files (assuming files exist)
$floors = [
    'groundfloor' => 'UC-MAIN-UPDATED (WITH DIMENSIONS)-1.svg', // Assuming this is your ground floor
    'mezzanine' => 'UC-MAIN-UPDATED (WITH DIMENSIONS)-2.svg', 
    'floor_2' => 'UC-MAIN-UPDATED (WITH DIMENSIONS)-3.svg',
    'floor_3' => 'UC-MAIN-UPDATED (WITH DIMENSIONS)-4.svg',
    'floor_4' => 'UC-MAIN-UPDATED (WITH DIMENSIONS)-5.svg',
    'floor_5' => 'UC-MAIN-UPDATED (WITH DIMENSIONS)-6.svg',
    'floor_6' => 'UC-MAIN-UPDATED (WITH DIMENSIONS)-7.svg',
    'floor_7' => 'UC-MAIN-UPDATED (WITH DIMENSIONS)-8.svg',
];
// Determine the initial floor to load
$initial_floor_key = 'groundfloor';
$initial_map_src = '../../assets/img/' . $floors[$initial_floor_key];


// Fetch all available rooms with their status
try {
    $pdo = get_db_connection();
    $stmt = $pdo->query("SELECT id, room_name, capacity, room_type, location_description, is_available, map_x, map_y, floor FROM rooms ORDER BY room_name");
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching rooms: " . $e->getMessage());
    $rooms = [];
}

// Handle header path based on role
$header_path = '../../templates/user/header_user.php';
if (isset($user['role'])) {
    if ($user['role'] === 'admin') {
        $header_path = '../../templates/admin/header_admin.php';
    } elseif ($user['role'] === 'faculty') {
        $header_path = '../../templates/faculty/header_faculty.php';
    }
}
require_once $header_path;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>


    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">


    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>
    <link rel="stylesheet" as="style" onload="this.rel='stylesheet'"
        href="https://fonts.googleapis.com/css2?display=swap&family=Noto+Sans:wght@400;500;700;900&family=Space+Grotesk:wght@400;500;700">


    <link rel="icon" type="image/x-icon"
        href="https://res.cloudinary.com/deua2yipj/image/upload/v1758917007/ChronoNav_logo_muon27.png">


    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-dark: #101518;
            --secondary-text: #5c748a;
            --border-color: #e5e7eb;
            --accent-blue: #2e78c6;
            --light-bg: #f9fafb;
            --available-color: #10b981;
            --unavailable-color: #ef4444;
        }

        body {
            background-color: #ffffff;
            background: #ffff;
            font-family: "Space Grotesk", "Noto Sans", sans-serif;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

        .map-main-container {
            display: flex;
            min-height: 100vh;
        }

        .map-container {
            flex: 1;
            padding: 30px 40px;
            min-height: 100vh;
            max-width: 1600px;
            margin: 0 auto;
            width: 100%;
            box-sizing: border-box;
            margin-left: 20%;
            background-color: #ffffff;
        }

        .map-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }

        .map-content {
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 30px;
            height: calc(100vh - 180px);
            min-height: 600px;
        }

        .map-viewer {
            background: white;
            border-radius: 0.75rem;
            overflow: hidden;
            border: 1px solid var(--border-color);
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .map-viewer-header {
            padding: 20px 25px;
            border-bottom: 1px solid var(--border-color);
            background: var(--light-bg);
        }

        .map-viewer-header h2 {
            font-size: 22px;
            font-weight: 700;
            color: var(--primary-dark);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .map-viewer-header h2 i {
            color: var(--accent-blue);
        }

        .map-display-container {
            flex: 1;
            padding: 25px;
            position: relative;
            overflow: hidden;
            background: #f8fafc;
        }

        #mapWrapper {
            position: relative;
            width: 100%;
            height: 100%;
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            background: white;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow: auto;
        }

        #mapContainer {
            position: relative;
            width: 100%;
            height: 100%;
            transform-origin: 0 0;
            transition: transform 0.3s ease;
        }

        #campusMap {
            width: 100%;
            height: 100%;
            min-height: 500px;
            border: none;
            display: block;
            background: white;
        }

        .map-controls {
            position: absolute;
            bottom: 20px;
            right: 20px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            z-index: 100;
        }

        .map-control-btn {
            width: 44px;
            height: 44px;
            border-radius: 0.5rem;
            background: white;
            border: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            color: var(--primary-dark);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .map-control-btn:hover {
            background: var(--accent-blue);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(46, 120, 198, 0.2);
        }

        .map-control-btn:active {
            transform: translateY(0);
        }

        .map-info-panel {
            padding: 15px 25px;
            background: var(--light-bg);
            border-top: 1px solid var(--border-color);
            font-size: 14px;
            color: var(--secondary-text);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .map-info-panel strong {
            color: var(--primary-dark);
            font-weight: 600;
        }

        .map-sidebar {
            background: white;
            border-radius: 0.75rem;
            overflow: hidden;
            border: 1px solid var(--border-color);
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .sidebar-header {
            padding: 20px 25px;
            border-bottom: 1px solid var(--border-color);
            background: var(--light-bg);
        }

        .sidebar-header h3 {
            font-size: 18px;
            font-weight: 700;
            color: var(--primary-dark);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-header h3 i {
            color: var(--accent-blue);
        }

        .sidebar-content {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
        }

        .search-container {
            margin-bottom: 20px;
        }

        .search-box {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            font-size: 14px;
            color: var(--primary-dark);
            background-color: white;
            transition: all 0.3s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .search-box:focus {
            outline: none;
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 3px rgba(46, 120, 198, 0.1);
        }

        .search-box::placeholder {
            color: #94a3b8;
        }

        #roomsList {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .room-item {
            padding: 18px;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            border-left: 4px solid var(--accent-blue);
            background-color: white;
            border: 1px solid var(--border-color);
        }

        .room-item:hover {
            background: #f0f7ff;
            transform: translateX(3px);
            border-color: var(--accent-blue);
            box-shadow: 0 2px 8px rgba(46, 120, 198, 0.1);
        }

        .room-item.selected-room {
            background: #e0f2fe;
            border-left-color: #0284c7;
            box-shadow: 0 2px 8px rgba(2, 132, 199, 0.15);
        }

        .room-item.unavailable {
            border-left-color: var(--unavailable-color);
            background-color: #fef2f2;
            opacity: 0.9;
        }

        .room-item-name {
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 6px;
            font-size: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .room-item-type {
            font-size: 13px;
            color: var(--secondary-text);
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .room-item-type i {
            font-size: 12px;
        }

        .room-item-capacity {
            font-size: 12px;
            color: #737373;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .room-item-capacity i {
            font-size: 11px;
        }

        .room-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 600;
            margin-top: 8px;
        }

        .room-status i {
            font-size: 10px;
        }

        .room-status.available {
            background: #d1fae5;
            color: #065f46;
        }

        .room-status.unavailable {
            background: #fee2e2;
            color: #991b1b;
        }

        .sidebar-footer {
            padding: 20px;
            border-top: 1px solid var(--border-color);
            background: var(--light-bg);
        }

        .legend-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .legend-title i {
            color: var(--accent-blue);
        }

        .legend-items {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            font-size: 13px;
            color: #374151;
        }

        .legend-color {
            width: 16px;
            height: 16px;
            border-radius: 0.25rem;
            margin-right: 12px;
            flex-shrink: 0;
        }

        /* Page Header Styles */
        .page-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 5px;
        }

        .btn-back {
            background: #eaedf1;
            color: #101518;
            width: 48px;
            height: 48px;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            border: none;
            padding: 0;
            flex-shrink: 0;
        }

        .btn-back:hover {
            background: #dce8f3;
            transform: translateX(-3px);
            color: var(--accent-blue);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .page-title h1 {
            font-size: 28px;
            font-weight: 800;
            color: var(--primary-dark);
            margin: 0;
            line-height: 1.2;
        }

        .page-title .subtitle {
            font-size: 15px;
            color: var(--secondary-text);
            margin-top: 4px;
        }

        .zoom-indicator {
            display: flex;
            align-items: center;
            gap: 10px;
            background: var(--light-bg);
            padding: 8px 16px;
            border-radius: 0.5rem;
            font-size: 14px;
            color: var(--secondary-text);
        }

        .zoom-indicator strong {
            color: var(--primary-dark);
        }

        /* Scrollbar Styling */
        ::-webkit-scrollbar {
            width: 12px;
            height: 12px;
        }

        ::-webkit-scrollbar-track {
            background: #ffffff;
            /* white track */
        }

        ::-webkit-scrollbar-thumb {
            background-color: #737373;
            /* gray thumb */
            border-radius: 6px;
            border: 3px solid #ffffff;
            /* padding effect with white border */
        }

        ::-webkit-scrollbar-thumb:hover {
            background-color: #2e78c6;
            /* blue on hover */
        }

        /* Scrollbar styling to match dashboard */
        .sidebar-content::-webkit-scrollbar,
        #mapWrapper::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }

        .sidebar-content::-webkit-scrollbar-track,
        #mapWrapper::-webkit-scrollbar-track {
            background: #ffffff;
            border-radius: 4px;
        }

        .sidebar-content::-webkit-scrollbar-thumb,
        #mapWrapper::-webkit-scrollbar-thumb {
            background-color: #737373;
            border-radius: 5px;
            border: 2px solid #ffffff;
        }

        .sidebar-content::-webkit-scrollbar-thumb:hover,
        #mapWrapper::-webkit-scrollbar-thumb:hover {
            background-color: var(--accent-blue);
        }

        /* For Firefox */
        .sidebar-content,
        #mapWrapper {
            scrollbar-width: thin;
            scrollbar-color: #737373 #ffffff;
        }

        /* Responsive Design */
        @media (max-width: 767px) {
            .map-container {
                padding: 15px;
            }

            .map-content {
                grid-template-columns: 1fr;
                height: auto;
                gap: 20px;
            }

            .map-viewer,
            .map-sidebar {
                height: 500px;
            }

            .map-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .page-header {
                width: 100%;
            }

            .zoom-indicator {
                align-self: flex-start;
            }
        }

        @media (min-width: 768px) and (max-width: 1023px) {
            .map-container {
                padding: 20px 25px;
            }

            .map-content {
                grid-template-columns: 1fr;
                height: auto;
            }

            .map-viewer,
            .map-sidebar {
                height: 500px;
            }
        }

        @media (min-width: 1024px) and (max-width: 1279px) {
            .map-content {
                grid-template-columns: 1fr 280px;
            }
        }

        @media (min-width: 1280px) {
            .map-content {
                grid-template-columns: 1fr 320px;
            }
        }

        /* Dark mode support */
        body.dark-mode .map-viewer,
        body.dark-mode .map-sidebar,
        body.dark-mode #mapWrapper {
            background: #263645;
            border-color: #121A21;
            color: #E5E8EB;
        }

        body.dark-mode .map-viewer-header,
        body.dark-mode .sidebar-header,
        body.dark-mode .sidebar-footer {
            background: #121A21;
            border-color: #263645;
        }

        body.dark-mode .search-box {
            background: #121A21;
            border-color: #263645;
            color: #E5E8EB;
        }

        body.dark-mode .room-item {
            background: #121A21;
            border-color: #263645;
            color: #E5E8EB;
        }

        body.dark-mode .room-item:hover {
            background: #1C7DD6;
        }

        body.dark-mode .room-item-name {
            color: #E5E8EB;
        }

        body.dark-mode .room-item-type {
            color: #94ADC7;
        }

        .floor-selector {
            display: flex;
            gap: 5px; /* Spacing between buttons */
            padding: 10px 0;
            overflow-x: auto; /* Allow horizontal scrolling if many buttons */
            border-bottom: 1px solid #eee;
            margin-bottom: 15px;
        }

        .floor-selector button {
            white-space: nowrap; /* Prevent button text from wrapping */
            padding: 8px 12px;
            border: 1px solid #ccc;
            background-color: #f8f9fa;
            color: #333;
            cursor: pointer;
            border-radius: 5px;
            transition: background-color 0.2s, border-color 0.2s;
        }

        .floor-selector button:hover {
            background-color: #e2e6ea;
        }

        .floor-selector button.active-floor {
            background-color: var(--accent-blue, #007bff); /* Use an accent color for active */
            color: white;
            border-color: var(--accent-blue, #007bff);
            font-weight: bold;
        }
        
        .map-viewer-header {
            display: flex;
            flex-direction: column; /* Stack header and selector */
        }
    </style>
</head>

<body>
    <?php
    $sidenav_path = '../../templates/user/sidenav_user.php';
    if (isset($user['role'])) {
        if ($user['role'] === 'admin') {
            $sidenav_path = '../../templates/admin/sidenav_admin.php';
        } elseif ($user['role'] === 'faculty') {
            $sidenav_path = '../../templates/faculty/sidenav_faculty.php';
        }
    }
    require_once $sidenav_path;
    ?>

    <div class="map-main-container">
        <div class="map-container">

            <div class="map-header">
                <div class="page-header">
                    <a href="../../pages/user/dashboard.php" class="btn-back" title="Back to Dashboard">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <div class="page-title">
                        <h1>Campus Map Navigation</h1>
                        <div class="subtitle">Interactive campus map with room information and navigation</div>
                    </div>
                </div>

                <div class="zoom-indicator">
                    <i class="fas fa-search"></i>
                    <span>Zoom: <strong id="zoomLevel">100%</strong></span>
                </div>
            </div>


            <div class="map-content">

                <div class="map-viewer">
                    <div class="map-viewer-header">
                        <h2><i class="fas fa-map"></i> Campus Map</h2>
                        
                        <div class="floor-selector" id="floorSelector">
                            <?php 
                            $floor_names = [
                                'groundfloor' => 'Ground Floor',
                                'floor_1' => '1st Floor',
                                'floor_2' => '2nd Floor',
                                'floor_3' => '3rd Floor',
                                'floor_4' => '4th Floor',
                                'floor_5' => '5th Floor',
                                'floor_6' => '6th Floor',
                                'floor_7' => '7th Floor',
                            ];
                            foreach ($floors as $key => $file): 
                            ?>
                                <button 
                                    class="floor-btn <?= $key === $initial_floor_key ? 'active-floor' : '' ?>"
                                    data-floor-key="<?= $key ?>"
                                    data-map-src="../../assets/img/<?= $file ?>"
                                    title="View <?= $floor_names[$key] ?? $key ?>">
                                    <?= $floor_names[$key] ?? $key ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                        </div>

                    <div class="map-display-container">
                        <div id="mapWrapper">
                            <div id="mapContainer">
                                <iframe id="campusMap"
                                    src="<?= $initial_map_src ?>"
                                    title="University Campus Map" loading="lazy" referrerpolicy="no-referrer"></iframe>
                            </div>
                        </div>

                        <div class="map-controls">
                            <button class="map-control-btn" onclick="zoomIn()" title="Zoom In">
                                <i class="fas fa-plus"></i>
                            </button>
                            <button class="map-control-btn" onclick="zoomOut()" title="Zoom Out">
                                <i class="fas fa-minus"></i>
                            </button>
                            <button class="map-control-btn" onclick="resetZoom()" title="Reset Zoom">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                            <button class="map-control-btn" onclick="fitToScreen()" title="Fit to Screen">
                                <i class="fas fa-expand"></i>
                            </button>
                        </div>
                    </div>

                    <div class="map-info-panel">
                        <div>
                            <strong>Selected Room:</strong>
                            <span id="selectedRoomInfo" class="ms-1">None</span>
                        </div>
                        <div>
                            <i class="fas fa-mouse-pointer me-1"></i>
                            <span>Click on rooms in the sidebar to highlight them</span>
                        </div>
                    </div>
                </div>


                <div class="map-sidebar">
                    <div class="sidebar-header">
                        <h3><i class="fas fa-building"></i> Available Rooms</h3>
                    </div>

                    <div class="sidebar-content">
                        <div class="search-container">
                            <input type="text" id="searchRooms" class="search-box"
                                placeholder="Search rooms by name or type...">
                        </div>

                        <div id="roomsList">
                            <?php if (empty($rooms)): ?>
                                <div class="text-center py-4 text-muted">
                                    <i class="fas fa-door-closed fa-2x mb-3"></i>
                                    <p>No rooms available at the moment.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($rooms as $room): ?>
                                    <div class="room-item <?= !$room['is_available'] ? 'unavailable' : '' ?>"
                                        data-room-id="<?= $room['id'] ?>"
                                        data-room-name="<?= htmlspecialchars($room['room_name']) ?>"
                                        data-room-type="<?= htmlspecialchars($room['room_type']) ?>"
                                        data-room-capacity="<?= $room['capacity'] ?? 0 ?>"
                                        data-room-location="<?= htmlspecialchars($room['location_description'] ?? '') ?>"
                                        data-room-floor="<?= htmlspecialchars($room['floor'] ?? '') ?>"> <div class="room-item-name">
                                            <span><?= htmlspecialchars($room['room_name']) ?></span>
                                            <?php if ($room['capacity']): ?>
                                                <small class="text-muted"><?= $room['capacity'] ?> seats</small>
                                            <?php endif; ?>
                                        </div>
                                        <div class="room-item-type">
                                            <i
                                                class="fas fa-<?= $room['room_type'] === 'Classroom' ? 'chalkboard-teacher' :
                                                    ($room['room_type'] === 'Laboratory' ? 'flask' :
                                                        ($room['room_type'] === 'Office' ? 'user-tie' : 'building')) ?>"></i>
                                            <?= htmlspecialchars($room['room_type']) ?>
                                        </div>
                                        <?php if ($room['location_description']): ?>
                                            <div class="room-item-capacity">
                                                <i class="fas fa-map-marker-alt"></i>
                                                <span><?= htmlspecialchars($room['location_description']) ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <span class="room-status <?= $room['is_available'] ? 'available' : 'unavailable' ?>">
                                            <i
                                                class="fas fa-<?= $room['is_available'] ? 'check-circle' : 'times-circle' ?>"></i>
                                            <?= $room['is_available'] ? 'Available' : 'Unavailable' ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="sidebar-footer">
                        <div class="legend-title">
                            <i class="fas fa-key"></i>
                            <span>Legend</span>
                        </div>
                        <div class="legend-items">
                            <div class="legend-item">
                                <div class="legend-color" style="background: var(--available-color);"></div>
                                <span>Available Room</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-color" style="background: var(--unavailable-color);"></div>
                                <span>Unavailable / Under Renovation</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-color"
                                    style="background: var(--accent-blue); border: 2px solid #ffffff;"></div>
                                <span>Selected Room</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/jquery.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Initialize variables
            let currentZoom = 1;
            const minZoom = 0.3;
            const maxZoom = 3;
            const zoomStep = 0.2;
            let selectedRoom = null;
            let currentFloor = '<?= $initial_floor_key ?>'; // Store current floor key

            // DOM Elements
            const roomsList = document.getElementById('roomsList');
            const searchBox = document.getElementById('searchRooms');
            const selectedRoomInfo = document.getElementById('selectedRoomInfo');
            const zoomLevel = document.getElementById('zoomLevel');
            const mapContainer = document.getElementById('mapContainer');
            const mapWrapper = document.getElementById('mapWrapper');
            const campusMap = document.getElementById('campusMap');
            const floorSelector = document.getElementById('floorSelector');
            const roomItems = roomsList.querySelectorAll('.room-item'); // Get all room items once

            // Apply initial zoom
            applyZoom();

            // Function to filter rooms based on search term and current floor
            function filterRooms() {
                const searchTerm = searchBox.value.toLowerCase().trim();
                let hasVisibleRoom = false;
                
                roomItems.forEach(item => {
                    const roomName = item.dataset.roomName.toLowerCase();
                    const roomType = item.dataset.roomType.toLowerCase();
                    const location = item.dataset.roomLocation.toLowerCase();
                    const roomFloor = item.dataset.roomFloor.toLowerCase();

                    const matchesSearch = (searchTerm === '' ||
                        roomName.includes(searchTerm) ||
                        roomType.includes(searchTerm) ||
                        location.includes(searchTerm));
                    
                    const matchesFloor = (roomFloor === currentFloor || roomFloor === ''); // Empty floor data shows on all maps or filter logic needs refinement

                    if (matchesSearch && matchesFloor) {
                        item.style.display = 'flex';
                        item.style.flexDirection = 'column';
                        hasVisibleRoom = true;
                    } else {
                        item.style.display = 'none';
                    }
                });

                // Display a message if no rooms are found after filtering
                const noRoomsMessage = roomsList.querySelector('.text-center');
                if (!hasVisibleRoom && !noRoomsMessage) {
                    const messageDiv = document.createElement('div');
                    messageDiv.className = 'text-center py-4 text-muted';
                    messageDiv.innerHTML = '<i class="fas fa-door-closed fa-2x mb-3"></i><p>No rooms found for this floor/search criteria.</p>';
                    roomsList.appendChild(messageDiv);
                } else if (hasVisibleRoom && noRoomsMessage) {
                     // Remove the 'No rooms found' message if rooms are visible
                     noRoomsMessage.remove();
                }

                // If the selected room is now hidden, unselect it
                if (selectedRoom) {
                    const selectedItem = roomsList.querySelector(`[data-room-id="${selectedRoom}"]`);
                    if (selectedItem && selectedItem.style.display === 'none') {
                        unselectRoom();
                    }
                }
            }
            
            // Function to unselect the current room
            function unselectRoom() {
                roomsList.querySelectorAll('.room-item').forEach(item => {
                    item.classList.remove('selected-room');
                });
                selectedRoom = null;
                selectedRoomInfo.textContent = 'None';
                // TODO: Clear highlight on map
            }

            // Search functionality
            searchBox.addEventListener('input', filterRooms);

            // Floor Selection functionality (NEW)
            floorSelector.addEventListener('click', function (e) {
                const floorButton = e.target.closest('.floor-btn');
                if (floorButton) {
                    // Update active button class
                    floorSelector.querySelectorAll('.floor-btn').forEach(btn => {
                        btn.classList.remove('active-floor');
                    });
                    floorButton.classList.add('active-floor');
                    
                    // Update map source
                    const newMapSrc = floorButton.dataset.mapSrc;
                    campusMap.src = newMapSrc;
                    currentFloor = floorButton.dataset.floorKey;
                    
                    // Reset zoom and highlight on map change
                    resetZoom();
                    unselectRoom();
                    filterRooms(); // Filter room list for the new floor
                }
            });


            // Room selection
            roomsList.addEventListener('click', function (e) {
                const roomItem = e.target.closest('.room-item');
                if (roomItem) {
                    // Remove previous selection
                    roomsList.querySelectorAll('.room-item').forEach(item => {
                        item.classList.remove('selected-room');
                    });

                    // Add selection to clicked room
                    roomItem.classList.add('selected-room');
                    selectedRoom = roomItem.dataset.roomId;

                    // Update info panel
                    selectedRoomInfo.textContent = roomItem.dataset.roomName;

                    // Scroll to show selected room
                    roomItem.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

                    // TODO: Highlight room on map (if coordinates are available)
                    highlightRoomOnMap(roomItem.dataset.roomName);
                }
            });

            // Zoom functionality (rest remains the same)
            // ... (keep all your original zoom, pan, and fitToScreen functions) ...

            // Zoom functionality
            window.zoomIn = function () {
                if (currentZoom < maxZoom) {
                    currentZoom += zoomStep;
                    applyZoom();
                }
            };

            window.zoomOut = function () {
                if (currentZoom > minZoom) {
                    currentZoom -= zoomStep;
                    applyZoom();
                }
            };

            window.resetZoom = function () {
                currentZoom = 1;
                applyZoom();
            };

            window.fitToScreen = function () {
                const wrapperRect = mapWrapper.getBoundingClientRect();
                const containerRect = mapContainer.getBoundingClientRect();

                // Calculate zoom to fit container within wrapper
                const mapIframe = document.getElementById('campusMap');
                const iframeContentWidth = mapIframe.contentWindow?.document.body.scrollWidth || containerRect.width;
                const iframeContentHeight = mapIframe.contentWindow?.document.body.scrollHeight || containerRect.height;


                // Use the iframe's content size for calculation if available, otherwise fallback to container size
                const scaleX = wrapperRect.width / iframeContentWidth;
                const scaleY = wrapperRect.height / iframeContentHeight;
                currentZoom = Math.min(scaleX, scaleY) * 0.95; // 95% to add some padding

                // Ensure zoom stays within bounds
                currentZoom = Math.max(minZoom, Math.min(currentZoom, maxZoom));
                applyZoom();

                // Center the map
                mapWrapper.scrollLeft = (iframeContentWidth * currentZoom - wrapperRect.width) / 2;
                mapWrapper.scrollTop = (iframeContentHeight * currentZoom - wrapperRect.height) / 2;
            };

            function applyZoom() {
                mapContainer.style.transform = `scale(${currentZoom})`;
                zoomLevel.textContent = `${Math.round(currentZoom * 100)}%`;

                // Update wrapper scroll behavior
                updateWrapperScroll();
            }

            function updateWrapperScroll() {
                // Adjust scroll position to maintain center-ish view
                const scrollLeft = mapWrapper.scrollLeft;
                const scrollTop = mapWrapper.scrollTop;
                const scaleChange = currentZoom / (parseFloat(mapContainer.style.transform?.replace('scale(', '') || 1));

                if (scaleChange !== 1) {
                    const newScrollLeft = scrollLeft * scaleChange;
                    const newScrollTop = scrollTop * scaleChange;

                    // Use requestAnimationFrame for smooth transition
                    requestAnimationFrame(() => {
                        mapWrapper.scrollLeft = newScrollLeft;
                        mapWrapper.scrollTop = newScrollTop;
                    });
                }
            }

            // Mouse wheel zoom support
            mapWrapper.addEventListener('wheel', function (e) {
                if (e.ctrlKey || e.metaKey) {
                    e.preventDefault();

                    const rect = mapWrapper.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;

                    const oldScale = currentZoom;

                    if (e.deltaY < 0) {
                        // Zoom in
                        if (currentZoom < maxZoom) {
                            currentZoom += zoomStep;
                        }
                    } else {
                        // Zoom out
                        if (currentZoom > minZoom) {
                            currentZoom -= zoomStep;
                        }
                    }

                    if (oldScale !== currentZoom) {
                        // Calculate new scroll position to zoom around cursor
                        const scaleChange = currentZoom / oldScale;
                        mapWrapper.scrollLeft = x * scaleChange - (x - mapWrapper.scrollLeft);
                        mapWrapper.scrollTop = y * scaleChange - (y - mapWrapper.scrollTop);

                        applyZoom();
                    }
                }
            }, { passive: false });

            // Touch gesture support for mobile
            let initialDistance = null;

            mapWrapper.addEventListener('touchstart', function (e) {
                if (e.touches.length === 2) {
                    e.preventDefault();
                    initialDistance = getTouchDistance(e.touches[0], e.touches[1]);
                }
            }, { passive: false });

            mapWrapper.addEventListener('touchmove', function (e) {
                if (e.touches.length === 2 && initialDistance !== null) {
                    e.preventDefault();
                    const currentDistance = getTouchDistance(e.touches[0], e.touches[1]);
                    const scaleChange = currentDistance / initialDistance;

                    const newZoom = currentZoom * scaleChange;
                    if (newZoom >= minZoom && newZoom <= maxZoom) {
                        currentZoom = newZoom;
                        applyZoom();
                    }
                }
            }, { passive: false });

            mapWrapper.addEventListener('touchend', function (e) {
                if (e.touches.length < 2) {
                    initialDistance = null;
                }
            });

            function getTouchDistance(touch1, touch2) {
                const dx = touch1.clientX - touch2.clientX;
                const dy = touch1.clientY - touch2.clientY;
                return Math.sqrt(dx * dx + dy * dy);
            }


            // Highlight room on map (placeholder function)
            function highlightRoomOnMap(roomName) {
                console.log(`Highlighting room: ${roomName}`);
                // This function would need to interface with the SVG map
                // For now, we'll just show a message
                selectedRoomInfo.textContent = `${roomName} (Click to navigate)`;
            }

            // Initialize:
            // 1. Filter room list based on the initial floor
            filterRooms(); 

            // 2. Select first available room on the initial floor
            const firstAvailableRoom = roomsList.querySelector('.room-item:not(.unavailable):not([style*="display: none"])');
            if (firstAvailableRoom) {
                firstAvailableRoom.click();
            }

            // 3. Fit map to screen on initial load and whenever the map iframe loads new content
            function fitMapOnLoad() {
                setTimeout(() => {
                    fitToScreen();
                }, 500); // Small delay to ensure iframe content is loaded and measured
            }
            campusMap.onload = fitMapOnLoad; // Attach to iframe load event
            fitMapOnLoad(); // Call once for initial load
        });
        (function () {
            const faviconUrl = "https://res.cloudinary.com/deua2yipj/image/upload/v1758917007/ChronoNav_logo_muon27.png";

            // Remove any existing favicons
            document.querySelectorAll('link[rel="icon"], link[rel="shortcut icon"]').forEach(link => link.remove());

            // Create a new favicon link
            const link = document.createElement("link");
            link.rel = "icon";
            link.type = "image/png";
            link.href = faviconUrl;

            // Append to head
            document.head.appendChild(link);
        })();
    </script>

    <?php require_once '../../templates/footer.php'; ?>

    <?php include('../../includes/semantics/footer.php'); ?>
</body>

</html>


<script>
    document.body.style.backgroundColor = "#ffffff";
</script>