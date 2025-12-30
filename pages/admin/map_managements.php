<?php
// CHRONONAV_WEB_DOSS/pages/admin/map_management.php
require_once '../../middleware/auth_check.php';
require_once '../../includes/db_connect.php';
require_once '../../includes/functions.php';

requireRole(['admin']);

$user = $_SESSION['user'];
$page_title = "Campus Map Management";
$current_page = "map_management";

// Configuration for buildings
$floors_main = [
    'groundfloor' => 'UC-MAIN-UPDATED (WITH DIMENSIONS)-1.svg',
    'mezzanine'   => 'UC-MAIN-UPDATED (WITH DIMENSIONS)-2.svg',
    'floor_2'     => 'UC-MAIN-UPDATED (WITH DIMENSIONS)-3.svg',
    'floor_3'     => 'UC-MAIN-UPDATED (WITH DIMENSIONS)-4.svg',
    'floor_4'     => 'UC-MAIN-UPDATED (WITH DIMENSIONS)-5.svg',
    'floor_5'     => 'UC-MAIN-UPDATED (WITH DIMENSIONS)-6.svg',
    'floor_6'     => 'UC-MAIN-UPDATED (WITH DIMENSIONS)-7.svg',
    'floor_7'     => 'UC-MAIN-UPDATED (WITH DIMENSIONS)-8.svg',
];

$floors_engr = [
    'engr_groundfloor' => 'Enginering College Building Ground Floor.svg',
    'engr_mezzanine'   => 'Enginering College Building Mezzanine Floor.svg',
    'engr_floor_2'     => 'Enginering College Building 2nd Floor.svg',
    'engr_floor_3'     => 'Enginering College Building 3rd Floor.svg',
    'engr_floor_4'     => 'Enginering College Building 4th Floor.svg',
    'engr_floor_5'     => 'Enginering College Building 5th Floor.svg',
    'engr_floor_6'     => 'Enginering College Building 6th Floor.svg',
    'engr_floor_7'     => 'Enginering College Building 7th Floor.svg',
    'engr_floor_8'     => 'Enginering College Building 8th Floor.svg',
    'engr_floor_9'     => 'Enginering College Building 9th Floor.svg',
    'engr_floor_10'    => 'Enginering College Building 10th Floor.svg',
];

$floor_names = [
    'groundfloor' => 'Main Ground', 'mezzanine' => 'Main Mezzanine', 'floor_2' => 'Main 2nd',
    'floor_3' => 'Main 3rd', 'floor_4' => 'Main 4th', 'floor_5' => 'Main 5th',
    'floor_6' => 'Main 6th', 'floor_7' => 'Main 7th',
    'engr_groundfloor' => 'Eng. Ground', 'engr_mezzanine' => 'Eng. Mezzanine',
    'engr_floor_2' => 'Eng. 2nd', 'engr_floor_3' => 'Eng. 3rd', 'engr_floor_4' => 'Eng. 4th',
    'engr_floor_5' => 'Eng. 5th', 'engr_floor_6' => 'Eng. 6th', 'engr_floor_7' => 'Eng. 7th',
    'engr_floor_8' => 'Eng. 8th', 'engr_floor_9' => 'Eng. 9th', 'engr_floor_10' => 'Eng. 10th',
];

// AJAX Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];
    try {
        $pdo = get_db_connection();
        if ($action === 'update_room_details' || $action === 'add_room') {
            $room_name = trim($_POST['room_name']);
            $capacity = (int) $_POST['capacity'];
            $room_type = $_POST['room_type'];
            $location_description = trim($_POST['location_description']);
            $is_available = isset($_POST['is_available']) ? 1 : 0;
            $floor = $_POST['floor'];

            if ($action === 'update_room_details') {
                $room_id = (int) $_POST['room_id'];
                $stmt = $pdo->prepare("UPDATE rooms SET room_name=?, capacity=?, room_type=?, location_description=?, is_available=?, floor=? WHERE id=?");
                $stmt->execute([$room_name, $capacity, $room_type, $location_description, $is_available, $floor, $room_id]);
                echo json_encode(['success' => true, 'message' => 'Room updated successfully']);
            } else {
                $stmt = $pdo->prepare("INSERT INTO rooms (room_name, capacity, room_type, location_description, is_available, floor) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$room_name, $capacity, $room_type, $location_description, $is_available, $floor]);
                echo json_encode(['success' => true, 'message' => 'Room added successfully']);
            }
        }
        exit;
    } catch (PDOException $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

try {
    $pdo = get_db_connection();
    $rooms = $pdo->query("SELECT * FROM rooms ORDER BY room_name")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $rooms = []; }

require_once '../../templates/admin/header_admin.php';
require_once '../../templates/admin/sidenav_admin.php';
?>

<style>
    :root {
        --primary-indigo: #6366f1;
        --slate-900: #0f172a;
        --slate-700: #334155;
        --slate-100: #f1f5f9;
        --glass-bg: rgba(255, 255, 255, 0.9);
    }

    .admin-main-wrapper {
        margin-left: 480px;
        padding: 40px;
        background-color: #f8fafc;
        min-height: 100vh;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .card {
        border: none;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
        background: var(--glass-bg);
        backdrop-filter: blur(10px);
        overflow: hidden;
        margin-bottom: 2rem;
    }

    .card-header {
        background: #fff !important;
        border-bottom: 1px solid var(--slate-100);
        padding: 1.5rem;
    }

    .map-display-container { 
        background: #e2e8f0; 
        position: relative; 
        height: 700px; 
        cursor: grab;
        overflow: hidden;
    }

    .map-frame { 
        width: 100%; 
        height: 100%; 
        border: none; 
        transition: transform 0.2s cubic-bezier(0.1, 0.7, 0.1, 1); 
        transform-origin: 0 0; 
        pointer-events: none;
    }

    .floor-btn { 
        background: transparent !important;
        color: var(--slate-700) !important;
        border: 1px solid var(--slate-100) !important;
        border-radius: 10px !important;
        margin: 0 3px;
        font-weight: 500;
        padding: 8px 16px !important;
    }

    .floor-btn.active-floor { 
        background: var(--primary-indigo) !important; 
        color: white !important; 
        border-color: var(--primary-indigo) !important;
    }

    .rooms-table thead th {
        background: #fff;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.05em;
        padding: 1rem 1.5rem;
        border-bottom: 2px solid var(--slate-100);
    }

    /* Modal Styling */
    .modal { display: none; backdrop-filter: blur(8px); background: rgba(15, 23, 42, 0.4); }
    .modal.active { display: flex; align-items: center; justify-content: center; z-index: 1050; position: fixed; top: 0; left: 0; width: 100%; height: 100%; }
    .modal-content { border-radius: 24px; padding: 30px; max-width: 600px; background: white; }

    @media (max-width: 992px) { .admin-main-wrapper { margin-left: 0; padding: 20px; } }
</style>

<div class="admin-main-wrapper">
    <div class="container-fluid">
        <div class="row mb-5 align-items-center">
            <div class="col-md-8">
                <h2 class="fw-bold text-slate-900 mb-1">Campus Map Architecture</h2>
                <p class="text-muted">Manage room locations and building layouts.</p>
            </div>
            <div class="col-md-4 text-md-end">
                <button class="btn btn-primary px-4 py-2 rounded-3" onclick="openAddRoomModal()" style="background-color: var(--primary-indigo); border:none;">
                    <i class="fas fa-plus-circle me-2"></i> Register New Room
                </button>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-university me-2 text-primary"></i>Main Building</h5>
                    <div id="floorSelector" class="btn-group overflow-auto">
                        <?php foreach ($floors_main as $key => $file): ?>
                            <button class="btn btn-sm floor-btn <?= $key === 'groundfloor' ? 'active-floor' : '' ?>" 
                                    data-map-src="../../assets/img/<?= $file ?>" data-floor-key="<?= $key ?>">
                                <?= str_replace('Main ', '', $floor_names[$key]) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="map-display-container" id="mainMapContainer">
                <iframe id="mainMap" class="map-frame" src="../../assets/img/<?= $floors_main['groundfloor'] ?>"></iframe>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-cog me-2 text-secondary"></i>Engineering Complex</h5>
                    <div id="engrFloorSelector" class="btn-group overflow-auto">
                        <?php foreach ($floors_engr as $key => $file): ?>
                            <button class="btn btn-sm floor-btn <?= $key === 'engr_groundfloor' ? 'active-floor' : '' ?>" 
                                    data-map-src="../../assets/img/<?= $file ?>" data-floor-key="<?= $key ?>">
                                <?= str_replace('Eng. ', '', $floor_names[$key]) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="map-display-container" id="engrMapContainer">
                <iframe id="engrMap" class="map-frame" src="../../assets/img/<?= $floors_engr['engr_groundfloor'] ?>"></iframe>
            </div>
        </div>

        <div class="card shadow-sm mb-5">
            <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-3">
                <h5 class="mb-0 fw-bold">Live Room Registry</h5>
                <div class="input-group" style="max-width: 300px;">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" id="tableSearch" class="form-control border-start-0 ps-0 shadow-none" placeholder="Search rooms...">
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 rooms-table" id="roomRegistryTable">
                        <thead>
                            <tr>
                                <th>Room Identity</th>
                                <th>Classification</th>
                                <th>Level/Floor</th>
                                <th>Occupancy</th>
                                <th>Operational Status</th>
                            </tr>
                        </thead>
                        <tbody id="roomTableBody">
                            <?php foreach ($rooms as $room): ?>
                                <tr class="room-row" style="cursor:pointer;" onclick='openEditRoomModal(<?= json_encode($room) ?>)'>
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark searchable-name"><?= htmlspecialchars($room['room_name']) ?></div>
                                        <small class="text-muted"><?= substr(htmlspecialchars($room['location_description']), 0, 30) ?>...</small>
                                    </td>
                                    <td class="searchable-type"><span class="badge rounded-pill bg-light text-dark border px-3"><?= $room['room_type'] ?></span></td>
                                    <td class="searchable-floor"><span class="text-slate-700"><?= $floor_names[$room['floor']] ?? $room['floor'] ?></span></td>
                                    <td><i class="fas fa-users me-1 text-muted"></i> <?= $room['capacity'] ?></td>
                                    <td>
                                        <?php if($room['is_available']): ?>
                                            <span class="badge bg-success-subtle text-success px-3">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger-subtle text-danger px-3">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr id="noResultsRow" style="display: none;">
                                <td colspan="5" class="text-center py-5 text-muted">No rooms found matching your search.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="roomModal" class="modal">
    <div class="modal-content container shadow">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 id="modalTitle" class="mb-0 fw-bold">Edit Room</h4>
            <button type="button" class="btn-close" onclick="closeRoomModal()"></button>
        </div>
        <form id="roomForm" onsubmit="saveRoom(event)">
            <input type="hidden" id="roomId" name="room_id">
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label class="form-label small fw-bold text-uppercase">Identification</label>
                    <input type="text" id="roomName" name="room_name" class="form-control" required placeholder="e.g. Laboratory K508">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label small fw-bold text-uppercase">Type</label>
                    <select id="roomType" name="room_type" class="form-select">
                        <option value="Classroom">Classroom</option>
                        <option value="Laboratory">Laboratory</option>
                        <option value="Office">Office</option>
                        <option value="Study Hall">Study Hall</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label small fw-bold text-uppercase">Floor Placement</label>
                    <select id="roomFloor" name="floor" class="form-select">
                        <?php foreach ($floor_names as $key => $name): ?>
                            <option value="<?= $key ?>"><?= $name ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-12 mb-3">
                    <label class="form-label small fw-bold text-uppercase">Seating Capacity</label>
                    <input type="number" id="capacity" name="capacity" class="form-control">
                </div>
                <div class="col-md-12 mb-3">
                    <label class="form-label small fw-bold text-uppercase">Location Context</label>
                    <textarea id="location" name="location_description" class="form-control" rows="2"></textarea>
                </div>
                <div class="col-md-12 mb-4">
                    <div class="form-check form-switch p-3 bg-light rounded-3">
                        <input type="checkbox" id="isAvailable" name="is_available" class="form-check-input ms-0 me-3" value="1">
                        <label class="form-check-label fw-bold">Active Availability</label>
                    </div>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-light flex-grow-1 py-2" onclick="closeRoomModal()">Dismiss</button>
                <button type="submit" class="btn btn-primary flex-grow-1 py-2" style="background: var(--primary-indigo);">Commit Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
    const zoomConfig = { min: 0.5, max: 5.0, step: 0.2 };
    const mapStates = {
        main: { zoom: 1, x: 0, y: 0, isDragging: false, startX: 0, startY: 0, iframeId: 'mainMap', containerId: 'mainMapContainer' },
        engr: { zoom: 1, x: 0, y: 0, isDragging: false, startX: 0, startY: 0, iframeId: 'engrMap', containerId: 'engrMapContainer' }
    };

    function applyTransform(building) {
        const s = mapStates[building];
        const iframe = document.getElementById(s.iframeId);
        iframe.style.transform = `translate(${s.x}px, ${s.y}px) scale(${s.zoom})`;
    }

    function initMapControls(building) {
        const state = mapStates[building];
        const container = document.getElementById(state.containerId);
        container.addEventListener('wheel', (e) => {
            e.preventDefault();
            const delta = e.deltaY > 0 ? -zoomConfig.step : zoomConfig.step;
            state.zoom = Math.min(Math.max(state.zoom + delta, zoomConfig.min), zoomConfig.max);
            applyTransform(building);
        }, { passive: false });

        container.addEventListener('mousedown', (e) => {
            state.isDragging = true;
            state.startX = e.clientX - state.x;
            state.startY = e.clientY - state.y;
            container.style.cursor = 'grabbing';
        });

        window.addEventListener('mousemove', (e) => {
            if (!state.isDragging) return;
            state.x = e.clientX - state.startX;
            state.y = e.clientY - state.startY;
            applyTransform(building);
        });

        window.addEventListener('mouseup', () => { 
            state.isDragging = false; 
            container.style.cursor = 'grab';
        });
    }

    function setupFloorSelector(selectorId, building) {
        document.getElementById(selectorId).addEventListener('click', function(e) {
            const btn = e.target.closest('.floor-btn');
            if (!btn) return;
            document.querySelectorAll(`#${selectorId} .floor-btn`).forEach(b => b.classList.remove('active-floor'));
            btn.classList.add('active-floor');
            const iframe = document.getElementById(mapStates[building].iframeId);
            iframe.src = btn.dataset.mapSrc;
            mapStates[building].zoom = 1;
            mapStates[building].x = 0;
            mapStates[building].y = 0;
            applyTransform(building);
        });
    }

    // Integrated Live Search Functionality
    function initSearch() {
        const searchInput = document.getElementById('tableSearch');
        const rows = document.querySelectorAll('.room-row');
        const noResults = document.getElementById('noResultsRow');

        searchInput.addEventListener('input', function() {
            const term = this.value.toLowerCase().trim();
            let hasMatches = false;

            rows.forEach(row => {
                const name = row.querySelector('.searchable-name').textContent.toLowerCase();
                const type = row.querySelector('.searchable-type').textContent.toLowerCase();
                const floor = row.querySelector('.searchable-floor').textContent.toLowerCase();

                if (name.includes(term) || type.includes(term) || floor.includes(term)) {
                    row.style.display = "";
                    hasMatches = true;
                } else {
                    row.style.display = "none";
                }
            });

            noResults.style.display = hasMatches ? "none" : "";
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        setupFloorSelector('floorSelector', 'main');
        setupFloorSelector('engrFloorSelector', 'engr');
        initMapControls('main');
        initMapControls('engr');
        initSearch(); // Initialize search
    });

    function openAddRoomModal() {
        document.getElementById('roomForm').reset();
        document.getElementById('roomId').value = '';
        document.getElementById('modalTitle').innerText = 'Register New Room';
        document.getElementById('roomModal').classList.add('active');
    }

    function openEditRoomModal(room) {
        document.getElementById('modalTitle').innerText = 'Modify: ' + room.room_name;
        document.getElementById('roomId').value = room.id;
        document.getElementById('roomName').value = room.room_name;
        document.getElementById('roomType').value = room.room_type;
        document.getElementById('roomFloor').value = room.floor;
        document.getElementById('capacity').value = room.capacity;
        document.getElementById('location').value = room.location_description;
        document.getElementById('isAvailable').checked = parseInt(room.is_available) === 1;
        document.getElementById('roomModal').classList.add('active');
    }

    function closeRoomModal() { document.getElementById('roomModal').classList.remove('active'); }

    function saveRoom(e) {
        e.preventDefault();
        const formData = new FormData(document.getElementById('roomForm'));
        formData.append('action', formData.get('room_id') ? 'update_room_details' : 'add_room');

        fetch(window.location.href, { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if(data.success) location.reload();
            else alert(data.message);
        });
    }
</script>

<?php require_once '../../templates/footer.php'; ?>