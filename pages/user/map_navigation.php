<?php
// CHRONONAV_WEB_DOSS/pages/user/map_navigation.php
// Map navigation page for students

require_once '../../middleware/auth_check.php';
require_once '../../includes/db_connect.php';

$user = $_SESSION['user'];
$page_title = "Campus Map - Navigation";
$current_page = "map";

// Fetch all available rooms with their status
try {
    $pdo = get_db_connection();
    $stmt = $pdo->query("SELECT id, room_name, capacity, room_type, location_description, is_available, map_x, map_y FROM rooms ORDER BY room_name");
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

<link rel="stylesheet" href="../../assets/css/user_css/map_navigation.css">

<style>
    .map-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }

    .map-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
    }

    .map-content {
        display: grid;
        grid-template-columns: 1fr 300px;
        gap: 20px;
    }

    .map-viewer {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        padding: 20px;
        position: relative;
    }

    .map-svg {
        width: 100%;
        height: auto;
        border: 1px solid #eee;
        border-radius: 4px;
    }

    .map-sidebar {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        padding: 20px;
        max-height: 600px;
        overflow-y: auto;
    }

    .room-item {
        padding: 12px;
        margin-bottom: 10px;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.3s ease;
        border-left: 4px solid #007bff;
    }

    .room-item:hover {
        background: #f0f0f0;
        transform: translateX(5px);
    }

    .room-item.unavailable {
        border-left-color: #dc3545;
        opacity: 0.6;
    }

    .room-item-name {
        font-weight: 600;
        color: #333;
        margin-bottom: 5px;
    }

    .room-item-type {
        font-size: 12px;
        color: #666;
    }

    .room-item-capacity {
        font-size: 12px;
        color: #999;
    }

    .room-status {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 3px;
        font-size: 11px;
        font-weight: 600;
        margin-top: 5px;
    }

    .room-status.available {
        background: #d4edda;
        color: #155724;
    }

    .room-status.unavailable {
        background: #f8d7da;
        color: #721c24;
    }

    .search-box {
        width: 100%;
        padding: 10px;
        margin-bottom: 15px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
    }

    .legend {
        margin-top: 20px;
        padding-top: 15px;
        border-top: 1px solid #eee;
    }

    .legend-item {
        display: flex;
        align-items: center;
        margin-bottom: 10px;
        font-size: 13px;
    }

    .legend-color {
        width: 20px;
        height: 20px;
        border-radius: 3px;
        margin-right: 10px;
    }

    @media (max-width: 768px) {
        .map-content {
            grid-template-columns: 1fr;
        }

        .map-sidebar {
            max-height: 300px;
        }
    }

    .selected-room {
        background: #e3f2fd;
        border-left-color: #2196F3;
    }

    .btn-back {
        background: #6c757d;
        color: white;
        width: 40px;
        height: 40px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        border: none;
        padding: 0;
    }

    .btn-back:hover {
        background: #5a6268;
        transform: translateX(-3px);
    }

    .zoom-controls {
        display: flex;
        gap: 8px;
    }

    .zoom-btn {
        background: #007bff;
        color: white;
        width: 40px;
        height: 40px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        padding: 0;
    }

    .zoom-btn:hover {
        background: #0056b3;
        transform: scale(1.1);
    }

    .zoom-btn:active {
        transform: scale(0.95);
    }
</style>

<div class="map-container">
    <div class="map-header">
        <div style="display: flex; align-items: center; gap: 15px;">
            <a href="../../pages/user/dashboard.php" class="btn-back" title="Back to Dashboard">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 256 256">
                    <path d="M224,128a8,8,0,0,1-8,8H59.31l58.35,58.34a8,8,0,1,1-11.32,11.32l-72-72a8,8,0,0,1,0-11.32l72-72a8,8,0,0,1,11.32,11.32L59.31,120H216A8,8,0,0,1,224,128Z"></path>
                </svg>
            </a>
            <h1 class="mb-0">Campus Map Navigation</h1>
        </div>
        <div class="zoom-controls">
            <button class="zoom-btn" onclick="zoomIn()" title="Zoom In">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 256 256">
                    <path d="M228.24,219.76l-51.38-51.38a104.06,104.06,0,1,0-11.31,11.31l51.38,51.38a8,8,0,0,0,11.31-11.31ZM40,112a72,72,0,1,1,72,72A72.08,72.08,0,0,1,40,112Zm60-12a8,8,0,0,0-8,8v28H72a8,8,0,0,0,0,16h20v20a8,8,0,0,0,16,0V140h20a8,8,0,0,0,0-16H108V108A8,8,0,0,0,100,100Z"></path>
                </svg>
            </button>
            <button class="zoom-btn" onclick="zoomOut()" title="Zoom Out">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 256 256">
                    <path d="M228.24,219.76l-51.38-51.38a104.06,104.06,0,1,0-11.31,11.31l51.38,51.38a8,8,0,0,0,11.31-11.31ZM40,112a72,72,0,1,1,72,72A72.08,72.08,0,0,1,40,112Zm56-8a8,8,0,0,0-8,8v20a8,8,0,0,0,16,0V112A8,8,0,0,0,96,104Zm32,0a8,8,0,0,0-8,8v20a8,8,0,0,0,16,0V112A8,8,0,0,0,128,104Z"></path>
                </svg>
            </button>
            <button class="zoom-btn" onclick="resetZoom()" title="Reset Zoom">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 256 256">
                    <path d="M240,128a112,112,0,0,1-183.36,80.88,8,8,0,0,1,11.2-11.38A96,96,0,1,0,38.75,85.75a8.07,8.07,0,0,1-5.66-3.34,8,8,0,0,1,0-11.32A112,112,0,0,1,240,128Z"></path>
                </svg>
            </button>
        </div>
    </div>

    <div class="map-header">
        <p class="text-muted mb-0">Find your classrooms and facilities</p>

    <div class="map-content">
        <!-- Map Viewer -->
        <div class="map-viewer">
            <div id="mapContainer" style="position: relative; width: 100%; border: 1px solid #ddd; border-radius: 4px; overflow: auto; background: white; transform-origin: 0 0;">
                <!-- SVG map embedded directly -->
                <iframe id="campusMap" 
                        src="../../assets/img/UC-MAIN-UPDATED%20(WITH%20DIMENSIONS)-1.svg" 
                        style="width: 100%; height: 600px; border: none; border-radius: 4px; display: block; min-width: 100%;"
                        allowfullscreen>
                </iframe>
            </div>
            <div style="margin-top: 15px; padding: 10px; background: #f0f7ff; border-radius: 4px;">
                <p class="mb-0" style="font-size: 13px; color: #666;">
                    <strong>Selected Room:</strong> <span id="selectedRoomInfo">None</span> | <span id="zoomLevel">Zoom: 100%</span>
                </p>
            </div>
        </div>

        <!-- Sidebar with Room List -->
        <div class="map-sidebar">
            <h5 class="mb-3">Available Rooms</h5>
            <input type="text" id="searchRooms" class="search-box" placeholder="Search rooms...">
            
            <div id="roomsList">
                <?php foreach ($rooms as $room): ?>
                    <div class="room-item <?= !$room['is_available'] ? 'unavailable' : '' ?>" 
                         data-room-id="<?= $room['id'] ?>" 
                         data-room-name="<?= htmlspecialchars($room['room_name']) ?>">
                        <div class="room-item-name"><?= htmlspecialchars($room['room_name']) ?></div>
                        <div class="room-item-type"><?= $room['room_type'] ?></div>
                        <div class="room-item-capacity">Capacity: <?= $room['capacity'] ?? 'N/A' ?></div>
                        <span class="room-status <?= $room['is_available'] ? 'available' : 'unavailable' ?>">
                            <?= $room['is_available'] ? 'Available' : 'Unavailable' ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="legend">
                <strong style="font-size: 13px;">Legend</strong>
                <div class="legend-item">
                    <div class="legend-color" style="background: #4CAF50;"></div>
                    <span>Available Room</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background: #f44336;"></div>
                    <span>Unavailable/Renovation</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const roomsList = document.getElementById('roomsList');
    const searchBox = document.getElementById('searchRooms');
    const selectedRoomInfo = document.getElementById('selectedRoomInfo');

    // Search functionality
    searchBox.addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase();
        const roomItems = roomsList.querySelectorAll('.room-item');
        
        roomItems.forEach(item => {
            const roomName = item.dataset.roomName.toLowerCase();
            if (roomName.includes(searchTerm)) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    });

    // Room selection
    roomsList.addEventListener('click', function(e) {
        const roomItem = e.target.closest('.room-item');
        if (roomItem) {
            // Remove previous selection
            roomsList.querySelectorAll('.room-item').forEach(item => {
                item.classList.remove('selected-room');
            });
            
            // Add selection to clicked room
            roomItem.classList.add('selected-room');
            selectedRoomInfo.textContent = roomItem.dataset.roomName;
        }
    });
});

// Map Zoom Functionality
let currentZoom = 1;
const minZoom = 0.5;
const maxZoom = 3;
const zoomStep = 0.2;

function zoomIn() {
    if (currentZoom < maxZoom) {
        currentZoom += zoomStep;
        applyZoom();
    }
}

function zoomOut() {
    if (currentZoom > minZoom) {
        currentZoom -= zoomStep;
        applyZoom();
    }
}

function resetZoom() {
    currentZoom = 1;
    applyZoom();
}

function applyZoom() {
    const mapContainer = document.getElementById('mapContainer');
    const campusMap = document.getElementById('campusMap');
    const zoomLevel = document.getElementById('zoomLevel');
    
    // Apply zoom to the container
    mapContainer.style.transform = `scale(${currentZoom})`;
    mapContainer.style.transformOrigin = '0 0';
    
    // Adjust height based on zoom level
    const baseHeight = 600;
    mapContainer.style.height = (baseHeight * currentZoom) + 'px';
    
    // Update zoom display
    zoomLevel.textContent = `Zoom: ${Math.round(currentZoom * 100)}%`;
}

// Mouse wheel zoom support
document.getElementById('mapContainer').addEventListener('wheel', function(e) {
    e.preventDefault();
    
    if (e.deltaY < 0) {
        // Scroll up - zoom in
        zoomIn();
    } else {
        // Scroll down - zoom out
        zoomOut();
    }
}, { passive: false });
</script>

<?php require_once '../../templates/common/footer.php'; ?>
