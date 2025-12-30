<?php
// CHRONONAV_WEB_DOSS/pages/visitor/dashboard.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'visitor') {
    header("Location: ../../auth/login.php");
    exit();
}

require_once '../../config/db_connect.php';

$page_title = "Guest Dashboard";

// --- MAP CONFIGURATION ---
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
    'engr_ground' => 'Enginering College Building Ground Floor.svg',
    'engr_mezz'   => 'Enginering College Building Mezzanine Floor.svg',
    'engr_floor2' => 'Enginering College Building 2nd Floor.svg',
    'engr_floor3' => 'Enginering College Building 3rd Floor.svg',
    'engr_floor4' => 'Enginering College Building 4th Floor.svg',
    'engr_floor5' => 'Enginering College Building 5th Floor.svg',
    'engr_floor6' => 'Enginering College Building 6th Floor.svg',
    'engr_floor7' => 'Enginering College Building 7th Floor.svg',
    'engr_floor8' => 'Enginering College Building 8th Floor.svg',
    'engr_floor9' => 'Enginering College Building 9th Floor.svg',
    'engr_floor10'=> 'Enginering College Building 10th Floor.svg',
];

$floor_names = [
    'groundfloor' => 'Main Grd', 'mezzanine' => 'Main Mezz', 'floor_2' => 'Main 2nd',
    'floor_3' => 'Main 3rd', 'floor_4' => 'Main 4th', 'floor_5' => 'Main 5th',
    'floor_6' => 'Main 6th', 'floor_7' => 'Main 7th',
    'engr_ground' => 'Eng Grd', 'engr_mezz' => 'Eng Mezz', 'engr_floor2' => 'Eng 2nd',
    'engr_floor3' => 'Eng 3rd', 'engr_floor4' => 'Eng 4th', 'engr_floor5' => 'Eng 5th',
    'engr_floor6' => 'Eng 6th', 'engr_floor7' => 'Eng 7th', 'engr_floor8' => 'Eng 8th',
    'engr_floor9' => 'Eng 9th', 'engr_floor10' => 'Eng 10th',
];

$initial_floor_key = 'groundfloor';
$initial_map_src = '../../assets/img/' . $floors_main[$initial_floor_key];

$announcements = [];
$news_query = "SELECT title, content, published_at FROM announcements ORDER BY published_at DESC LIMIT 4";
$news_result = $conn->query($news_query);
if ($news_result) { $announcements = $news_result->fetch_all(MYSQLI_ASSOC); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - ChronoNav</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root { --primary: #007bff; --bg-soft: #f8fafc; --glass: rgba(255, 255, 255, 0.8); }
        body { background-color: var(--bg-soft); font-family: 'Space Grotesk', sans-serif; color: #1e293b; }
        .visitor-navbar { background: var(--glass); backdrop-filter: blur(10px); padding: 1rem 2rem; position: sticky; top: 0; z-index: 1020; box-shadow: 0 1px 0 rgba(0,0,0,0.05); }
        .visitor-welcome-card { background: linear-gradient(135deg, #1e293b 0%, #334155 100%); color: white; border-radius: 15px; padding: 2rem; margin-top: 1.5rem; }
        .map-viewer-container { background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; }
        .map-viewer-header { padding: 1.5rem; background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
        .floor-btn { border: 1px solid #e2e8f0; background: white; padding: 6px 12px; border-radius: 10px; font-size: 0.75rem; margin-bottom: 5px; transition: 0.3s; }
        .floor-btn.active-floor { background: #0f172a; color: white; border-color: #0f172a; }
        .map-display-area { height: 750px; width: 100%; overflow: hidden; position: relative; background: #f1f5f9; cursor: grab; }
        #mapContainer { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; transform-origin: center; transition: transform 0.1s ease; }
        #campusMap { width: 100%; height: 100%; border: none; pointer-events: none; }
        .search-results { position: absolute; top: 100%; left: 0; right: 0; background: white; border-radius: 12px; box-shadow: 0 15px 30px rgba(0,0,0,0.1); z-index: 1100; max-height: 250px; overflow-y: auto; display: none; border: 1px solid #e2e8f0; }
        .search-item { padding: 10px 15px; cursor: pointer; border-bottom: 1px solid #f1f5f9; }
        .room-card { border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px; margin-bottom: 8px; background: white; transition: 0.2s; cursor: pointer; }
        .room-card.highlight { border: 2px solid var(--primary); background: #f0f7ff; }
        .zoom-controls { position: absolute; bottom: 20px; right: 20px; display: flex; flex-direction: column; gap: 8px; z-index: 10; }
        .zoom-btn { width: 40px; height: 40px; border-radius: 10px; border: none; background: white; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <nav class="visitor-navbar d-flex">
        <a href="../../index.php" class="text-decoration-none text-dark fw-bold"><i class="fas fa-arrow-left me-2"></i> Back</a>
        <div class="ms-auto d-flex align-items-center gap-3">
            <button class="btn btn-danger btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#emergencyModal">Emergency</button>
            <img src="https://res.cloudinary.com/deua2yipj/image/upload/v1758917007/ChronoNav_logo_muon27.png" width="30">
        </div>
    </nav>

    <div class="container pb-5">
        <div class="visitor-welcome-card mb-4">
            <h2 class="fw-bold">Campus Navigation</h2>
            <p class="opacity-75">Access room directories and live maps for both campus buildings.</p>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="map-viewer-container">
                    <div class="map-viewer-header">
                        <div class="position-relative mb-4">
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
                                <input type="text" id="roomSearch" class="form-control" placeholder="Search rooms (e.g. K908, Library, Dean)">
                                <button class="btn btn-primary" id="btnSearch">Find</button>
                            </div>
                            <div id="searchResults" class="search-results"></div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="small fw-bold text-muted text-uppercase mb-2">Main Building</div>
                                <div class="d-flex flex-wrap gap-1" id="mainSelector">
                                    <?php foreach ($floors_main as $key => $file): ?>
                                        <button class="floor-btn <?= $key==='groundfloor'?'active-floor':'' ?>" data-floor-key="<?= $key ?>" data-map-src="../../assets/img/<?= $file ?>"><?= $floor_names[$key] ?></button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="col-md-6 border-start">
                                <div class="small fw-bold text-muted text-uppercase mb-2">Engineering Complex</div>
                                <div class="d-flex flex-wrap gap-1" id="engrSelector">
                                    <?php foreach ($floors_engr as $key => $file): ?>
                                        <button class="floor-btn" data-floor-key="<?= $key ?>" data-map-src="../../assets/img/<?= $file ?>"><?= $floor_names[$key] ?></button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="map-display-area">
                        <div class="zoom-controls">
                            <button class="zoom-btn" onclick="zoomIn()"><i class="fas fa-plus"></i></button>
                            <button class="zoom-btn" onclick="zoomOut()"><i class="fas fa-minus"></i></button>
                            <button class="zoom-btn" onclick="resetZoom()"><i class="fas fa-sync-alt"></i></button>
                        </div>
                        <div id="mapContainer">
                            <iframe id="campusMap" src="<?= $initial_map_src ?>"></iframe>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div id="summaryCard" class="card border-0 shadow-sm p-3 mb-3 bg-white" style="display:none;">
                    <h6 class="fw-bold text-primary mb-2" id="summaryTitle"></h6>
                    <div id="summaryContent" style="font-size: 0.85rem;"></div>
                </div>

                <div class="card border-0 shadow-sm p-3 bg-white">
                    <h6 class="fw-bold mb-3 border-bottom pb-2">Room Directory</h6>
                    <div id="roomList" class="overflow-auto" style="max-height: 450px;"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // 1. FLOOR SUMMARIES (YOUR DATA)
        const floorSummaries = {
          'groundfloor': {
                title: 'Ground Floor Summary',
                categories: [
                    {cat: 'Academic Rooms',count: 7,items: 'Room 102, GSR 8, GSR 9, GSR 10, GSR 11, GSR 12, GH'},
                    {cat: 'Administrative Offices',count: 5,items: 'Accounting Office, Cashier, Registrar, EOP, HD'},
                    {cat: 'Service & Facilities',count: 6,items: 'Canteen, Canteen Stall, Textbook Center, Medical/Dental Clinic, DVD Room, ETEEAP Room'},
                    {cat: 'Functional Areas',count: 6,items: 'High School Activity Center, Study Hall (2), Parking Area (2), Female CR, Male CR'},
                    {cat: 'Access Points',count: 4,items: 'Gate 1, Gate 2, Gate 3, Gate 4'}
                ]
            },

            'mezzanine': {
                title: 'Mezzanine Summary',
                categories: [
                    {cat: 'Academic Rooms',count: 16,items: 'GSR 1–GSR 6, Room M28A, Room M28B, Room M28C, Room M29, Room M30, Room M41, Room M42, Room M43, Demo Room M27B, CTE Mini AVR (M18)'},
                    {cat: 'Offices',count: 12,items: 'Graduate School Office, UCTA, CTE Faculty Room, Education Dean’s Office, Yearbook Office, IMS Office, HN Link Office, Guidance Service Center, Registrar (M27), Server Room, Campus Ministry, Comprehensive Office'},
                    {cat: 'Service & Facilities',count: 3,items: 'GS Library, Chorus Hall, University Chapel'},
                    {cat: 'Functional Areas',count: 4,items: 'Male CR (2), Female CR (2)'}
                ]
            },

            'floor_2': {
                title: '2nd Floor Summary',
                categories: [
                    {cat: 'Academic Rooms',count: 31,items: 'Rooms 217–220, 221–223 (CBE), 224–233, 231A, 231B, 231C, 235–237, 239, AB Mini-AVR (234), Psychology Laboratory, Political Science & English, Allied Engineering Computer Laboratory'},
                    {cat: 'Offices',count: 22,items: 'Executive Vice Chancellor, Chancellor’s Office, President’s Office, Legal Office, VC Academics, VC External Affairs, VC Finance, Tel Operator, AB Dean’s Office, AB Faculty Room, AB Psychology Program Faculty, Athletics/Transportation/Internal Audit, HR Office, Institutional Planning, QA Office, Alumni Office, Purchasing, HR Office, 2 Research Offices'},
                    {cat: 'Service & Facilities',count: 1,items: 'Main Library'},
                    {cat: 'Functional Areas',count: 7,items: '3 Female CRs, 3 Male CRs, 1 New Conference Room'},
                    {cat: 'Stairs',count: 5,items: '5 Staircase locations (labeled UP or DN) providing vertical access between floors'}
                ]
            },
            'floor_3': {
                title: '3rd Floor Summary',
                categories: [
                    {cat: 'Academic Rooms',count: 35,items: 'Rooms 311–319, 322–324, 329, 337, 338, 345–348, Criminology AVR (Rooms 331 A, B, C), Commerce Computer Labs (x2), Simulation Room, Forensic Science Lab, Demonstration Room 340'},
                    {cat: 'Offices',count: 9,items: 'Criminology Faculty Room (335), Criminology Dean\'s Office (336), Research Community Extension Office (334), Police Intern Office (339), Commerce Faculty Room, Commerce Dean\'s Office, Accounting Room, Consultation Room (321)'},
                    {cat: 'Service & Facilities',count: 1,items: 'College Library (Main Library)'},
                    {cat: 'Functional Areas',count: 6,items: '3 Female CRs, 3 Male CRs'},
                    {cat: 'Stairs',count: 6,items: '6 Staircase access points (labeled UP or DN)'}
                ]
            },

            'floor_4': {
                title: '4th Floor Summary',
                categories: [
                    {cat: 'Academic Rooms',count: 33,items: 'Rooms 418A–418E, 419A–419C, 420–423, SMART Wireless Lab (401), Speech Lab, Project Design & Elec Machine Area, Internet Room, Analog Section Laboratory, Digital Section Laboratory, Micro-processor Laboratory'},
                    {cat: 'Laboratories',count: 8,items: 'Forensic Ballistic Lab, Crime Scene Room, Forensic Photography, Biology Lab (415, 416), Chemistry Lab (414, 415), Computer Engineering Cisco Lab, Physics Lab'},
                    {cat: 'Offices',count: 2,items: 'Office (next to 401), Librarian\'s Office'},
                    {cat: 'Service & Facilities',count: 1,items: 'Main Library (Upper Level/Branch)'},
                    {cat: 'Functional Areas',count: 4,items: 'Male Student CR, Female Student CR, Chemistry Lab Stockroom, Biology Lab Stockroom'},
                    {cat: 'Stairs',count: 6,items: '6 Staircase access points (labeled UP or DN)'}
                ]
            }, 
            'floor_5': {
                title: '5th Floor Summary',
                categories: [
                    {cat: 'Academic Rooms',count: 22,items: 'Rooms 513–516, 517, 521–523, 530, 530A–530C, 535–537, 539, 540, Multimedia Rooms (x2), Speech Labs 519 & 520'},
                    {cat: 'Laboratories',count: 6,items: 'Chemistry Lab 534, Computer Labs 524, 526, 528, 542, 544'},
                    {cat: 'Offices',count: 6,items: 'Faculty Room, Dean\'s Office, Campus Research Lab, PSITS Office, CSP-S Office, Borrower Section'},
                    {cat: 'Service & Facilities',count: 1,items: 'Canteen'},
                    {cat: 'Functional Areas',count: 6,items: '3 Male CRs, 3 Female CRs'},
                    {cat: 'Stairs',count: 6,items: '6 Staircase access points (labeled UP or DN)'}
                ]
            },

            'floor_6': {
                title: '6th Floor Summary',
                categories: [
                    {cat: 'Academic Rooms',count: 32,items: 'Rooms 611–616, 617–623, 624–631, 631A–631C, 640–643, 640E–643E'},
                    {cat: 'Specialized Labs',count: 7,items: 'UC Restaurant, UC Bar, HRM Lab 2 (637), Baking & Pastry Lab, Kitchen Lab 1, Bartending Room, Bartender\'s Room (632)'},
                    {cat: 'Offices/Storage',count: 1,items: 'General Storage'},
                    {cat: 'Functional Areas',count: 6,items: '3 Male CRs, 3 Female CRs, Pipe Chase areas'},
                    {cat: 'Stairs',count: 6,items: '6 Staircase access points (labeled UP or DN)'}
                ]
            },

            'floor_7': {
                title: '7th Floor Summary',
                categories: [
                    {cat: 'Academic Rooms',count: 12,items: 'Rooms 722, 723, 726, 727, 728, 730A, 730B, 730C, 733, 734, and 3 P.E. Classrooms'},
                    {cat: 'Specialized Facilities',count: 4,items: 'HRM Mini Hotel, Housekeeping Room, Criminology Gym, High School Roof Deck'},
                    {cat: 'Offices',count: 3,items: 'HRM Office Deans, HRM Faculty Room, P.E. Office'},
                    {cat: 'Functional Areas',count: 4,items: 'Male CR (M CR), Female CR (F CR), Male CR (near gym), Water Tank'},
                    {cat: 'Stairs & Access',count: 3,items: '2 main staircases, Elevator access'}
                ]
            }
        };
        


        // 2. FULL ROOM DATABASE (INCLUDING ALL ENGR ROOMS)
        const engrRooms = [
            { name: "Main Entrance (Eng)", floor: "engr_ground", label: "Eng. Ground" },
            { name: "Dean's Office (Eng)", floor: "engr_mezz", label: "Eng. Mezzanine" },
            { name: "Faculty Room (Eng)", floor: "engr_mezz", label: "Eng. Mezzanine" },
            ...Array.from({length: 12}, (_, i) => ({ name: `Room K2${(i+8).toString().padStart(2, '0')}`, floor: "engr_floor2", label: "Eng. 2nd" })),
            ...Array.from({length: 12}, (_, i) => ({ name: `Room K3${(i+8).toString().padStart(2, '0')}`, floor: "engr_floor3", label: "Eng. 3rd" })),
            ...Array.from({length: 12}, (_, i) => ({ name: `Room K4${(i+8).toString().padStart(2, '0')}`, floor: "engr_floor4", label: "Eng. 4th" })),
            ...Array.from({length: 12}, (_, i) => ({ name: `Room K5${(i+8).toString().padStart(2, '0')}`, floor: "engr_floor5", label: "Eng. 5th" })),
            ...Array.from({length: 15}, (_, i) => ({ name: `Room K6${(i+5).toString().padStart(2, '0')}`, floor: "engr_floor6", label: "Eng. 6th" })),
            ...Array.from({length: 12}, (_, i) => ({ name: `Room K7${(i+8).toString().padStart(2, '0')}`, floor: "engr_floor7", label: "Eng. 7th" })),
            ...Array.from({length: 12}, (_, i) => ({ name: `Room K8${(i+8).toString().padStart(2, '0')}`, floor: "engr_floor8", label: "Eng. 8th" })),
            ...Array.from({length: 12}, (_, i) => ({ name: `Room K9${(i+8).toString().padStart(2, '0')}`, floor: "engr_floor9", label: "Eng. 9th" })),
            ...Array.from({length: 12}, (_, i) => ({ name: `Room K10${(i+8).toString().padStart(2, '0')}`, floor: "engr_floor10", label: "Eng. 10th" }))
        ];

        const mainRooms = [
            { name: "Accounting Office", floor: "groundfloor", label: "Main Ground" },
            { name: "Cashier", floor: "groundfloor", label: "Main Ground" },
            { name: "Registrar", floor: "groundfloor", label: "Main Ground" },
            { name: "Main Library", floor: "floor_2", label: "Main 2nd" }
        ];

        const allRooms = [...mainRooms, ...engrRooms];
        const campusMap = document.getElementById('campusMap');
        const mapContainer = document.getElementById('mapContainer');
        const searchInput = document.getElementById('roomSearch');
        const resultsDiv = document.getElementById('searchResults');
        const roomListDiv = document.getElementById('roomList');

        function updateUI(floorKey, highlightRoom = null) {
            // Update Summary
            const summary = floorSummaries[floorKey];
            const sCard = document.getElementById('summaryCard');
            if(summary) {
                sCard.style.display = 'block';
                document.getElementById('summaryTitle').innerText = summary.title;
                document.getElementById('summaryContent').innerHTML = summary.categories.map(c => 
                    `<div class="mb-1"><strong>${c.cat}:</strong> ${c.items}</div>`
                ).join('');
            } else { sCard.style.display = 'none'; }

            // Update List
            roomListDiv.innerHTML = '';
            const floorRooms = allRooms.filter(r => r.floor === floorKey);
            if(floorRooms.length === 0) { roomListDiv.innerHTML = '<small class="text-muted">No rooms listed.</small>'; }
            floorRooms.forEach(room => {
                const card = document.createElement('div');
                card.className = `room-card ${highlightRoom === room.name ? 'highlight' : ''}`;
                card.innerHTML = `<span class="fw-bold">${room.name}</span><br><small class="text-muted">${room.label}</small>`;
                roomListDiv.appendChild(card);
                if(highlightRoom === room.name) card.scrollIntoView({behavior:'smooth', block:'center'});
            });
        }

        function switchFloor(btn) {
            document.querySelectorAll('.floor-btn').forEach(b => b.classList.remove('active-floor'));
            btn.classList.add('active-floor');
            campusMap.src = btn.dataset.mapSrc;
            updateUI(btn.dataset.floorKey);
            resetZoom();
        }

        document.querySelectorAll('.floor-btn').forEach(btn => {
            btn.onclick = () => switchFloor(btn);
        });

        searchInput.oninput = () => {
            const val = searchInput.value.toLowerCase().trim();
            resultsDiv.innerHTML = '';
            if(!val) { resultsDiv.style.display = 'none'; return; }
            const matches = allRooms.filter(r => r.name.toLowerCase().includes(val));
            if(matches.length > 0) {
                matches.slice(0, 8).forEach(m => {
                    const d = document.createElement('div');
                    d.className = 'search-item';
                    d.innerHTML = `<strong>${m.name}</strong><br><small class="text-muted">${m.label}</small>`;
                    d.onclick = () => {
                        const btn = document.querySelector(`[data-floor-key="${m.floor}"]`);
                        if(btn) switchFloor(btn);
                        updateUI(m.floor, m.name);
                        resultsDiv.style.display = 'none';
                        searchInput.value = m.name;
                    };
                    resultsDiv.appendChild(d);
                });
                resultsDiv.style.display = 'block';
            }
        };

        let zoom = 1;
        function zoomIn() { zoom = Math.min(3, zoom + 0.2); mapContainer.style.transform = `scale(${zoom})`; }
        function zoomOut() { zoom = Math.max(0.5, zoom - 0.2); mapContainer.style.transform = `scale(${zoom})`; }
        function resetZoom() { zoom = 1; mapContainer.style.transform = `scale(1)`; }

        window.onload = () => updateUI('groundfloor');
    </script>
</body>
</html>