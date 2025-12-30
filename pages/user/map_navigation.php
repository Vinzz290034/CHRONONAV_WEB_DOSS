<?php
// CHRONONAV_WEB_DOSS/pages/user/map_navigation.php
require_once '../../middleware/auth_check.php';
require_once '../../includes/db_connect.php'; 

$user = $_SESSION['user'];
$page_title = "Campus Map Navigation";
$current_page = "map";

$img_path = "../../assets/img/";

// 1. FLOOR DEFINITIONS
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

// 2. FETCH DATABASE ROOMS (Main Building)
try {
    $pdo = get_db_connection();
    $stmt = $pdo->query("SELECT room_name, floor FROM rooms WHERE is_available = 1 AND floor NOT LIKE 'engr_%' ORDER BY room_name");
    $main_rooms_db = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $main_rooms_db = []; }

// 3. HEADER & SIDENAV
$role = $user['role'] ?? 'student';
$header_path = "../../templates/{$role}/header_{$role}.php";
$sidenav_path = "../../templates/{$role}/sidenav_{$role}.php";

require_once $header_path;
require_once $sidenav_path;
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
    /* General Layout & Page Transition */
    .map-main-wrapper {
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        padding-left: 480px; /* Adjusted from 480px to match modern sidebar standards */
        background-color: #fcfcfd;
    }

    /* Modern Glassmorphism Card Style */
    .card {
        border: none;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(10px);
        overflow: hidden;
        margin-bottom: 1.5rem;
    }

    .card-header {
        background: #ffffff !important;
        color: #1e293b !important;
        border-bottom: 1px solid #f1f5f9;
        padding: 1rem 1.25rem;
        font-weight: 600;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    /* Map Display & Interaction Container */
    .map-display-container {
        height: 750px; /* Maintained your preferred height */
        background: #f1f5f9;
        overflow: hidden;
        position: relative;
        cursor: grab;
        border-radius: 0 0 16px 16px;
        transition: box-shadow 0.3s ease;
    }

    .map-display-container:active {
        cursor: grabbing;
    }

    /* Smooth Map Frame Zooming */
    .map-frame {
        width: 100%;
        height: 100%;
        border: none;
        pointer-events: none;
        transform-origin: 0 0;
        transition: transform 0.2s cubic-bezier(0.1, 0.7, 0.1, 1);
    }

    /* Minimalist Search Bar Styling */
    .input-group {
        background: #fff;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        transition: all 0.3s ease;
        overflow: hidden;
    }

    .input-group:focus-within {
        border-color: #6366f1;
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
    }

    #campusSearch {
        border: none;
        padding: 12px;
        font-size: 0.95rem;
        box-shadow: none;
    }

    /* Interactive Floor Selector Buttons */
    .floor-btn {
        border: 1px solid #e2e8f0 !important;
        color: #64748b !important;
        background: transparent !important;
        border-radius: 8px !important;
        margin: 0 2px;
        padding: 5px 12px !important;
        font-size: 0.8rem !important;
        transition: all 0.2s ease !important;
    }

    .floor-btn:hover {
        background: #f8fafc !important;
        color: #1e293b !important;
    }

    .floor-btn.active {
        background: #6366f1 !important;
        color: #fff !important;
        border-color: #6366f1 !important;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        transform: translateY(-1px);
    }

    /* Room List Directory & Hover Animations */
    #searchResults {
        max-height: 1200px;
        overflow-y: auto;
    }

    .room-item {
        padding: 15px 20px;
        border-bottom: 1px solid #f1f5f9;
        border-left: 4px solid transparent;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        background: #fff;
        cursor: pointer;
    }

    .room-item:hover {
        background-color: #f8fafc;
        padding-left: 28px; /* Smooth slide-in effect */
    }

    /* Identification Borders */
    .room-item.engr-room { border-left-color: #94a3b8; }
    .room-item.main-room { border-left-color: #6366f1; }

    .room-item .fw-bold {
        color: #334155;
        font-size: 0.95rem;
    }

    .room-item .text-muted {
        font-size: 0.8rem;
        color: #94a3b8 !important;
    }

    /* Custom Minimalist Scrollbar */
    #searchResults::-webkit-scrollbar {
        width: 6px;
    }
    #searchResults::-webkit-scrollbar-track {
        background: #f1f5f9;
    }
    #searchResults::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 10px;
    }
    #searchResults::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
</style>

<div class="map-main-wrapper">
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-md-8">
                <h2 class="fw-bold"><i class="fas fa-map-marked-alt text-primary me-2"></i>Campus Navigation</h2>
            </div>
            <div class="col-md-4">
                <div class="input-group shadow-sm">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" id="campusSearch" class="form-control border-start-0" placeholder="Search rooms...">
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center flex-wrap g-2">
                        <h5 class="mb-0">Engineering Building</h5>
                        <div id="engrSelector" class="btn-group overflow-auto">
                            <?php foreach (array_reverse($floors_engr) as $key => $file): ?>
                                <button class="btn btn-sm btn-outline-light floor-btn <?= $key === 'engr_groundfloor' ? 'active' : '' ?>" 
                                        data-map-src="<?= $img_path . $file ?>" data-floor-key="<?= $key ?>">
                                    <?= str_replace('Eng. ', '', $floor_names[$key]) ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="map-display-container" id="engrMapContainer">
                        <iframe id="engrMap" class="map-frame" src="<?= $img_path . $floors_engr['engr_groundfloor'] ?>"></iframe>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center flex-wrap g-2">
                        <h5 class="mb-0">Main Building</h5>
                        <div id="mainSelector" class="btn-group overflow-auto">
                            <?php foreach ($floors_main as $key => $file): ?>
                                <button class="btn btn-sm btn-outline-light floor-btn <?= $key === 'groundfloor' ? 'active' : '' ?>" 
                                        data-map-src="<?= $img_path . $file ?>" data-floor-key="<?= $key ?>">
                                    <?= str_replace('Main ', '', $floor_names[$key]) ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="map-display-container" id="mainMapContainer">
                        <iframe id="mainMap" class="map-frame" src="<?= $img_path . $floors_main['groundfloor'] ?>"></iframe>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white fw-bold py-3"><i class="fas fa-list-ul text-primary me-2"></i>Campus Directory</div>
                    <div class="card-body p-0" id="searchResults"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // 1. COMPLETED STATIC DATA FOR ENGINEERING
    const engrRooms = [
        { name: "Entrance", floor: "engr_groundfloor", label: "Eng. Ground" },
        { name: "Elevator", floor: "engr_groundfloor", label: "Eng. Ground" },
        { name: "Faculty Room (Eng)", floor: "engr_mezzanine", label: "Eng. Mezzanine" },
        { name: "Dean's Office", floor: "engr_mezzanine", label: "Eng. Mezzanine" },
        
        // Loop-based addition for clean code
        ...Array.from({length: 12}, (_, i) => ({ name: `Room K2${i+8}`, floor: "engr_floor_2", label: "Eng. 2nd" })),
        ...Array.from({length: 12}, (_, i) => ({ name: `Room K3${i+8}`, floor: "engr_floor_3", label: "Eng. 3rd" })),
        ...Array.from({length: 12}, (_, i) => ({ name: `Room K4${i+8}`, floor: "engr_floor_4", label: "Eng. 4th" })),
        ...Array.from({length: 12}, (_, i) => ({ name: `Room K5${i+8}`, floor: "engr_floor_5", label: "Eng. 5th" })),
        ...Array.from({length: 15}, (_, i) => ({ name: `Room K6${(i+5).toString().padStart(2, '0')}`, floor: "engr_floor_6", label: "Eng. 6th" })),
        ...Array.from({length: 12}, (_, i) => ({ name: `Room K7${i+8}`, floor: "engr_floor_7", label: "Eng. 7th" })),
        
        // --- ADDED 8TH, 9TH, & 10TH FLOOR ROOMS ---
        ...Array.from({length: 12}, (_, i) => ({ name: `Room K8${i+8}`, floor: "engr_floor_8", label: "Eng. 8th" })),
        ...Array.from({length: 11}, (_, i) => ({ name: `Room K9${(i+8).toString().padStart(2, '0')}`, floor: "engr_floor_9", label: "Eng. 9th" })),
        ...Array.from({length: 12}, (_, i) => ({ name: `Room K10${(i+8).toString().padStart(2, '0')}`, floor: "engr_floor_10", label: "Eng. 10th" }))
        
    ].map(r => ({ ...r, type: 'engr' }));

    // 2. MERGE WITH DB ROOMS (Main Building)
    const mainRooms = <?php echo json_encode($main_rooms_db); ?>.map(r => ({
        name: r.room_name,
        floor: r.floor,
        label: <?php echo json_encode($floor_names); ?>[r.floor] || r.floor,
        type: 'main'
    }));

    const allRooms = [...engrRooms, ...mainRooms];

    // 3. SEARCH & RENDER
    function renderResults(filter = "") {
        const container = document.getElementById('searchResults');
        container.innerHTML = "";
        const filtered = allRooms.filter(r => r.name.toLowerCase().includes(filter.toLowerCase()));
        filtered.forEach(room => {
            const div = document.createElement('div');
            div.className = `room-item ${room.type}-room`;
            div.innerHTML = `
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-bold text-dark">${room.name}</div>
                        <small class="text-muted">${room.label}</small>
                    </div>
                </div>
            `;
            div.onclick = () => focusRoom(room.floor);
            container.appendChild(div);
        });
    }

    // 4. NAVIGATION LOGIC
    const mapStates = {
        main: { zoom: 1, x: 0, y: 0, isDragging: false, startX: 0, startY: 0, iframeId: 'mainMap', containerId: 'mainMapContainer' },
        engr: { zoom: 1, x: 0, y: 0, isDragging: false, startX: 0, startY: 0, iframeId: 'engrMap', containerId: 'engrMapContainer' }
    };

    function applyTransform(b) {
        const s = mapStates[b];
        const el = document.getElementById(s.iframeId);
        if(el) el.style.transform = `translate(${s.x}px, ${s.y}px) scale(${s.zoom})`;
    }

    function initControls(b) {
        const s = mapStates[b];
        const c = document.getElementById(s.containerId);
        if(!c) return;
        c.onwheel = (e) => {
            e.preventDefault();
            const delta = e.deltaY > 0 ? -0.1 : 0.1;
            s.zoom = Math.min(Math.max(s.zoom + delta, 0.5), 4);
            applyTransform(b);
        };
        c.onmousedown = (e) => {
            s.isDragging = true;
            s.startX = e.clientX - s.x; s.startY = e.clientY - s.y;
            c.style.cursor = 'grabbing';
        };
        window.addEventListener('mousemove', (e) => {
            if (!s.isDragging) return;
            s.x = e.clientX - s.startX; s.y = e.clientY - s.startY;
            applyTransform(b);
        });
        window.addEventListener('mouseup', () => { s.isDragging = false; c.style.cursor = 'grab'; });
    }

    function setupSelector(id, b) {
        const container = document.getElementById(id);
        if(!container) return;
        container.onclick = (e) => {
            const btn = e.target.closest('.floor-btn');
            if (!btn) return;
            container.querySelectorAll('.floor-btn').forEach(el => el.classList.remove('active'));
            btn.classList.add('active');
            const iframe = document.getElementById(mapStates[b].iframeId);
            if(iframe) iframe.src = btn.dataset.mapSrc;
            mapStates[b].zoom = 1; mapStates[b].x = 0; mapStates[b].y = 0;
            applyTransform(b);
        };
    }

    function focusRoom(floorKey) {
        const building = floorKey.startsWith('engr_') ? 'engr' : 'main';
        const btn = document.querySelector(`[data-floor-key="${floorKey}"]`);
        if (btn) {
            btn.click();
            document.getElementById(mapStates[building].containerId).scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    document.getElementById('campusSearch').oninput = (e) => renderResults(e.target.value);

    document.addEventListener('DOMContentLoaded', () => {
        initControls('main'); initControls('engr');
        setupSelector('mainSelector', 'main'); setupSelector('engrSelector', 'engr');
        renderResults();
    });
</script>

<?php require_once '../../templates/footer.php'; ?>