<?php
// CHRONONAV_WEB_DOSS/pages/admin/map_management.php
// Map management page for admins - can edit room status and availability

require_once '../../middleware/auth_check.php';
require_once '../../includes/db_connect.php';
require_once '../../includes/functions.php';

requireRole(['admin']);

$user = $_SESSION['user'];
$page_title = "Campus Map Management";
$current_page = "map_management";  // Keep for title, but active link uses this value

// Handle room status updates via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];
    
    try {
        $pdo = get_db_connection();
        
        if ($action === 'update_room_status') {
            $room_id = (int)$_POST['room_id'];
            $is_available = (int)$_POST['is_available'];
            
            $stmt = $pdo->prepare("UPDATE rooms SET is_available = ? WHERE id = ?");
            $stmt->execute([$is_available, $room_id]);
            
            echo json_encode(['success' => true, 'message' => 'Room status updated successfully']);
        } elseif ($action === 'update_room_details') {
            $room_id = (int)$_POST['room_id'];
            $room_name = trim($_POST['room_name']);
            $capacity = (int)$_POST['capacity'];
            $room_type = $_POST['room_type'];
            $location_description = trim($_POST['location_description']);
            $is_available = (int)$_POST['is_available'];
            
            $stmt = $pdo->prepare("UPDATE rooms SET room_name = ?, capacity = ?, room_type = ?, location_description = ?, is_available = ? WHERE id = ?");
            $stmt->execute([$room_name, $capacity, $room_type, $location_description, $is_available, $room_id]);
            
            echo json_encode(['success' => true, 'message' => 'Room updated successfully']);
        } elseif ($action === 'add_room') {
            $room_name = trim($_POST['room_name']);
            $capacity = (int)$_POST['capacity'];
            $room_type = $_POST['room_type'];
            $location_description = trim($_POST['location_description']);
            
            $stmt = $pdo->prepare("INSERT INTO rooms (room_name, capacity, room_type, location_description, is_available) VALUES (?, ?, ?, ?, 1)");
            $stmt->execute([$room_name, $capacity, $room_type, $location_description]);
            
            echo json_encode(['success' => true, 'message' => 'Room added successfully']);
        }
        exit;
    } catch (PDOException $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// Fetch all rooms
try {
    $pdo = get_db_connection();
    $stmt = $pdo->query("SELECT id, room_name, capacity, room_type, location_description, is_available FROM rooms ORDER BY room_name");
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching rooms: " . $e->getMessage());
    $rooms = [];
}

$header_path = '../../templates/admin/header_admin.php';
require_once $header_path;
?>

<link rel="stylesheet" href="../../assets/css/admin_css/map_management.css">

<style>
    .map-management-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }

    .management-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
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

    .btn-add-room {
        background: #28a745;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 600;
    }

    .btn-add-room:hover {
        background: #218838;
    }

    .rooms-table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .rooms-table thead {
        background: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
    }

    .rooms-table th {
        padding: 15px;
        text-align: left;
        font-weight: 600;
        color: #333;
    }

    .rooms-table td {
        padding: 15px;
        border-bottom: 1px solid #dee2e6;
    }

    .rooms-table tr:hover {
        background: #f8f9fa;
    }

    .status-badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }

    .status-available {
        background: #d4edda;
        color: #155724;
    }

    .status-unavailable {
        background: #f8d7da;
        color: #721c24;
    }

    .action-buttons {
        display: flex;
        gap: 10px;
    }

    .btn-edit, .btn-delete, .btn-toggle {
        padding: 6px 12px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-edit {
        background: #007bff;
        color: white;
    }

    .btn-edit:hover {
        background: #0056b3;
    }

    .btn-toggle {
        background: #ffc107;
        color: white;
    }

    .btn-toggle:hover {
        background: #e0a800;
    }

    .btn-delete {
        background: #dc3545;
        color: white;
    }

    .btn-delete:hover {
        background: #c82333;
    }

    /* Modal Styles */
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 1000;
        align-items: center;
        justify-content: center;
    }

    .modal.active {
        display: flex;
    }

    .modal-content {
        background: white;
        padding: 30px;
        border-radius: 8px;
        width: 90%;
        max-width: 500px;
        box-shadow: 0 5px 30px rgba(0,0,0,0.3);
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        border-bottom: 1px solid #eee;
        padding-bottom: 15px;
    }

    .modal-close {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: #999;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 600;
        color: #333;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
        font-family: inherit;
    }

    .form-group textarea {
        resize: vertical;
        min-height: 80px;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #007bff;
        box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
    }

    .checkbox-group {
        display: flex;
        align-items: center;
        margin-top: 10px;
    }

    .checkbox-group input[type="checkbox"] {
        width: auto;
        margin-right: 10px;
    }

    .modal-buttons {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        margin-top: 20px;
    }

    .btn-save {
        background: #28a745;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 600;
    }

    .btn-save:hover {
        background: #218838;
    }

    .btn-cancel {
        background: #6c757d;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 600;
    }

    .btn-cancel:hover {
        background: #5a6268;
    }

    .alert {
        padding: 15px;
        border-radius: 4px;
        margin-bottom: 20px;
    }

    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .alert-error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
</style>

<div class="map-management-container">
    <div class="management-header">
        <div style="display: flex; align-items: center; gap: 15px;">
            <a href="../../pages/admin/dashboard.php" class="btn-back" title="Back to Dashboard">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 256 256">
                    <path d="M224,128a8,8,0,0,1-8,8H59.31l58.35,58.34a8,8,0,1,1-11.32,11.32l-72-72a8,8,0,0,1,0-11.32l72-72a8,8,0,0,1,11.32,11.32L59.31,120H216A8,8,0,0,1,224,128Z"></path>
                </svg>
            </a>
            <h1 class="mb-0">Campus Map Management</h1>
        </div>
        <button class="btn-add-room" onclick="openAddRoomModal()">+ Add New Room</button>
    </div>

    <div id="alertContainer"></div>

    <!-- Campus Map Viewer with Zoom Controls -->
    <div style="margin-bottom: 30px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h5 style="margin: 0; color: #111418; font-weight: 700;">Campus Map Overview</h5>
            <div class="zoom-controls" style="display: flex; gap: 8px;">
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
        <div id="mapContainer" style="position: relative; width: 100%; border: 1px solid #ddd; border-radius: 4px; overflow: auto; background: white; transform-origin: 0 0;">
            <!-- SVG map embedded directly with zoom capability -->
            <iframe id="campusMap" 
                    src="../../assets/img/UC-MAIN-UPDATED%20(WITH%20DIMENSIONS)-1.svg" 
                    style="width: 100%; height: 500px; border: none; border-radius: 4px; display: block; min-width: 100%;"
                    allowfullscreen>
            </iframe>
        </div>
        <p style="margin-top: 10px; font-size: 13px; color: #666;">Use zoom buttons or scroll with mouse to navigate. Click on rooms or manage them in the table below</p>
    </div>

    <table class="rooms-table">
        <thead>
            <tr>
                <th>Room Name</th>
                <th>Type</th>
                <th>Capacity</th>
                <th>Location</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="roomsTableBody">
            <?php foreach ($rooms as $room): ?>
                <tr data-room-id="<?= $room['id'] ?>">
                    <td><?= htmlspecialchars($room['room_name']) ?></td>
                    <td><?= $room['room_type'] ?></td>
                    <td><?= $room['capacity'] ?? 'N/A' ?></td>
                    <td><?= htmlspecialchars(substr($room['location_description'] ?? '', 0, 30)) ?></td>
                    <td>
                        <span class="status-badge <?= $room['is_available'] ? 'status-available' : 'status-unavailable' ?>">
                            <?= $room['is_available'] ? 'Available' : 'Unavailable' ?>
                        </span>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn-edit" onclick="openEditRoomModal(<?= $room['id'] ?>)">Edit</button>
                            <button class="btn-toggle" onclick="toggleRoomStatus(<?= $room['id'] ?>, <?= $room['is_available'] ? 1 : 0 ?>)">
                                <?= $room['is_available'] ? 'Mark Unavailable' : 'Mark Available' ?>
                            </button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Add/Edit Room Modal -->
<div id="roomModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Add New Room</h3>
            <button class="modal-close" onclick="closeRoomModal()">&times;</button>
        </div>

        <form id="roomForm" onsubmit="saveRoom(event)">
            <div class="form-group">
                <label for="roomName">Room Name/Number *</label>
                <input type="text" id="roomName" name="room_name" required>
            </div>

            <div class="form-group">
                <label for="roomType">Room Type *</label>
                <select id="roomType" name="room_type" required>
                    <option value="Classroom">Classroom</option>
                    <option value="Laboratory">Laboratory</option>
                    <option value="Lecture Hall">Lecture Hall</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <div class="form-group">
                <label for="capacity">Capacity (number of students)</label>
                <input type="number" id="capacity" name="capacity" min="1">
            </div>

            <div class="form-group">
                <label for="location">Location Description</label>
                <textarea id="location" name="location_description" placeholder="e.g., 3rd Floor, Main Building, Wing A"></textarea>
            </div>

            <div class="form-group checkbox-group">
                <input type="checkbox" id="isAvailable" name="is_available" value="1">
                <label for="isAvailable" style="margin: 0;">Mark as Available</label>
            </div>

            <div class="modal-buttons">
                <button type="button" class="btn-cancel" onclick="closeRoomModal()">Cancel</button>
                <button type="submit" class="btn-save">Save Room</button>
            </div>
        </form>
    </div>
</div>

<script>
let currentRoomId = null;

function openAddRoomModal() {
    currentRoomId = null;
    document.getElementById('modalTitle').textContent = 'Add New Room';
    document.getElementById('roomForm').reset();
    document.getElementById('isAvailable').checked = true;
    document.getElementById('roomModal').classList.add('active');
}

function openEditRoomModal(roomId) {
    currentRoomId = roomId;
    document.getElementById('modalTitle').textContent = 'Edit Room';
    document.getElementById('roomModal').classList.add('active');
    
    // Find the room data from the table
    const row = document.querySelector(`tr[data-room-id="${roomId}"]`);
    // In a real implementation, you'd fetch this data via AJAX
}

function closeRoomModal() {
    document.getElementById('roomModal').classList.remove('active');
    currentRoomId = null;
}

function saveRoom(event) {
    event.preventDefault();
    const formData = new FormData(document.getElementById('roomForm'));
    const action = currentRoomId ? 'update_room_details' : 'add_room';
    
    formData.append('action', action);
    if (currentRoomId) {
        formData.append('room_id', currentRoomId);
    }

    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            closeRoomModal();
            setTimeout(() => location.reload(), 1500);
        } else {
            showAlert(data.message, 'error');
        }
    })
    .catch(error => {
        showAlert('Error saving room: ' + error, 'error');
    });
}

function toggleRoomStatus(roomId, currentStatus) {
    const newStatus = currentStatus ? 0 : 1;
    const formData = new FormData();
    formData.append('action', 'update_room_status');
    formData.append('room_id', roomId);
    formData.append('is_available', newStatus);

    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(data.message, 'error');
        }
    })
    .catch(error => {
        showAlert('Error updating room status: ' + error, 'error');
    });
}

function showAlert(message, type) {
    const alertContainer = document.getElementById('alertContainer');
    const alertClass = type === 'success' ? 'alert-success' : 'alert-error';
    alertContainer.innerHTML = `<div class="alert ${alertClass}">${message}</div>`;
    
    setTimeout(() => {
        alertContainer.innerHTML = '';
    }, 5000);
}

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
    
    // Apply zoom to the container
    mapContainer.style.transform = `scale(${currentZoom})`;
    mapContainer.style.transformOrigin = '0 0';
    
    // Adjust height based on zoom level
    const baseHeight = 500;
    mapContainer.style.height = (baseHeight * currentZoom) + 'px';
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

// Close modal when clicking outside
document.getElementById('roomModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeRoomModal();
    }
});
</script>

<?php require_once '../../templates/footer.php'; ?>
