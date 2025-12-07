<?php
// CHRONONAV_WEB_DOSS/pages/admin/map_management.php
// Map management page for admins - can edit room status and availability

require_once '../../middleware/auth_check.php';
require_once '../../includes/db_connect.php';
require_once '../../includes/functions.php';

requireRole(['admin']);

$user = $_SESSION['user'];
$page_title = "Campus Map Management";
$current_page = "map_management";

// Handle room status updates via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    try {
        $pdo = get_db_connection();

        if ($action === 'update_room_status') {
            $room_id = (int) $_POST['room_id'];
            $is_available = (int) $_POST['is_available'];

            $stmt = $pdo->prepare("UPDATE rooms SET is_available = ? WHERE id = ?");
            $stmt->execute([$is_available, $room_id]);

            echo json_encode(['success' => true, 'message' => 'Room status updated successfully']);
        } elseif ($action === 'update_room_details') {
            $room_id = (int) $_POST['room_id'];
            $room_name = trim($_POST['room_name']);
            $capacity = (int) $_POST['capacity'];
            $room_type = $_POST['room_type'];
            $location_description = trim($_POST['location_description']);
            $is_available = (int) $_POST['is_available'];

            $stmt = $pdo->prepare("UPDATE rooms SET room_name = ?, capacity = ?, room_type = ?, location_description = ?, is_available = ? WHERE id = ?");
            $stmt->execute([$room_name, $capacity, $room_type, $location_description, $is_available, $room_id]);

            echo json_encode(['success' => true, 'message' => 'Room updated successfully']);
        } elseif ($action === 'add_room') {
            $room_name = trim($_POST['room_name']);
            $capacity = (int) $_POST['capacity'];
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

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>
    <link rel="stylesheet" as="style" onload="this.rel='stylesheet'"
        href="https://fonts.googleapis.com/css2?display=swap&family=Noto+Sans:wght@400;500;700;900&family=Space+Grotesk:wght@400;500;700">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon"
        href="https://res.cloudinary.com/deua2yipj/image/upload/v1758917007/ChronoNav_logo_muon27.png">

    <!-- Bootstrap CSS -->
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
            --warning-color: #f59e0b;
            --danger-color: #dc3545;
        }

        body {
            background-color: #ffffff;
            background: #ffff;
            font-family: "Space Grotesk", "Noto Sans", sans-serif;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

        .map-management-main-container {
            display: flex;
            min-height: 100vh;
        }

        .map-management-container {
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

        .management-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }

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

        .btn-add-room {
            background: var(--accent-blue);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-add-room:hover {
            background: #1c65b8;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(46, 120, 198, 0.2);
        }

        /* Map Viewer Section */
        .map-viewer-section {
            background: white;
            border-radius: 0.75rem;
            overflow: hidden;
            border: 1px solid var(--border-color);
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }

        .map-viewer-header {
            padding: 20px 25px;
            border-bottom: 1px solid var(--border-color);
            background: var(--light-bg);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .map-viewer-header h5 {
            font-size: 18px;
            font-weight: 700;
            color: var(--primary-dark);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .map-viewer-header h5 i {
            color: var(--accent-blue);
        }

        .zoom-controls {
            display: flex;
            gap: 8px;
        }

        .zoom-btn {
            background: white;
            color: var(--primary-dark);
            width: 44px;
            height: 44px;
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            padding: 0;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .zoom-btn:hover {
            background: var(--accent-blue);
            color: white;
            border-color: var(--accent-blue);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(46, 120, 198, 0.2);
        }

        .zoom-btn:active {
            transform: translateY(0);
        }

        .map-display-container {
            padding: 25px;
            position: relative;
            overflow: hidden;
            background: #f8fafc;
        }

        #mapContainer {
            position: relative;
            width: 100%;
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            background: white;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow: auto;
            transform-origin: 0 0;
            transition: transform 0.3s ease;
            height: 500px;
        }

        #campusMap {
            width: 100%;
            height: 100%;
            min-height: 500px;
            border: none;
            display: block;
            background: white;
        }

        .map-info-panel {
            padding: 15px 25px;
            background: var(--light-bg);
            border-top: 1px solid var(--border-color);
            font-size: 14px;
            color: var(--secondary-text);
        }

        /* Rooms Table */
        .rooms-table-container {
            background: white;
            border-radius: 0.75rem;
            overflow: hidden;
            border: 1px solid var(--border-color);
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
        }

        .table-header {
            padding: 20px 25px;
            border-bottom: 1px solid var(--border-color);
            background: var(--light-bg);
        }

        .table-header h3 {
            font-size: 18px;
            font-weight: 700;
            color: var(--primary-dark);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .table-header h3 i {
            color: var(--accent-blue);
        }

        .rooms-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: white;
        }

        .rooms-table thead {
            background: var(--light-bg);
            border-bottom: 2px solid var(--border-color);
        }

        .rooms-table th {
            padding: 16px 20px;
            text-align: left;
            font-weight: 600;
            color: var(--primary-dark);
            font-size: 14px;
            border-bottom: 1px solid var(--border-color);
        }

        .rooms-table td {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border-color);
            color: var(--primary-dark);
            font-size: 14px;
        }

        .rooms-table tr:hover {
            background: #f0f7ff;
        }

        .rooms-table tr:last-child td {
            border-bottom: none;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-available {
            background: #d1fae5;
            color: #065f46;
        }

        .status-unavailable {
            background: #fee2e2;
            color: #991b1b;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-edit,
        .btn-toggle,
        .btn-delete {
            padding: 8px 16px;
            border: none;
            border-radius: 0.375rem;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-edit {
            background: var(--accent-blue);
            color: white;
        }

        .btn-edit:hover {
            background: #1c65b8;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(46, 120, 198, 0.2);
        }

        .btn-toggle {
            background: var(--warning-color);
            color: white;
        }

        .btn-toggle:hover {
            background: #d97706;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(245, 158, 11, 0.2);
        }

        .btn-delete {
            background: var(--danger-color);
            color: white;
        }

        .btn-delete:hover {
            background: #b91c1c;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(220, 53, 69, 0.2);
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
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
            border-radius: 0.75rem;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 5px 30px rgba(0, 0, 0, 0.3);
            border: 1px solid var(--border-color);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 15px;
        }

        .modal-header h3 {
            font-size: 22px;
            font-weight: 700;
            color: var(--primary-dark);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--secondary-text);
            transition: color 0.3s ease;
        }

        .modal-close:hover {
            color: var(--primary-dark);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--primary-dark);
            font-size: 14px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            font-size: 14px;
            font-family: inherit;
            background: white;
            color: var(--primary-dark);
            transition: all 0.3s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 3px rgba(46, 120, 198, 0.1);
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .checkbox-group input[type="checkbox"] {
            width: auto;
            margin: 0;
            cursor: pointer;
        }

        .checkbox-group label {
            margin: 0;
            cursor: pointer;
        }

        .modal-buttons {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 25px;
        }

        .btn-save {
            background: var(--accent-blue);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .btn-save:hover {
            background: #1c65b8;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(46, 120, 198, 0.2);
        }

        .btn-cancel {
            background: #6c757d;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .btn-cancel:hover {
            background: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(108, 117, 125, 0.2);
        }

        /* Alert Styles */
        .alert {
            padding: 16px 20px;
            border-radius: 0.5rem;
            margin-bottom: 25px;
            border: 1px solid transparent;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-color: #a7f3d0;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-color: #fecaca;
        }

        /* Scrollbar Styling */
        ::-webkit-scrollbar {
            width: 12px;
            height: 12px;
        }

        ::-webkit-scrollbar-track {
            background: #ffffff;
        }

        ::-webkit-scrollbar-thumb {
            background-color: #737373;
            border-radius: 6px;
            border: 3px solid #ffffff;
        }

        ::-webkit-scrollbar-thumb:hover {
            background-color: #2e78c6;
        }

        #mapContainer::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }

        #mapContainer::-webkit-scrollbar-track {
            background: #ffffff;
            border-radius: 4px;
        }

        #mapContainer::-webkit-scrollbar-thumb {
            background-color: #737373;
            border-radius: 5px;
            border: 2px solid #ffffff;
        }

        #mapContainer::-webkit-scrollbar-thumb:hover {
            background-color: var(--accent-blue);
        }

        /* For Firefox */
        #mapContainer {
            scrollbar-width: thin;
            scrollbar-color: #737373 #ffffff;
        }

        /* Responsive Design */
        @media (max-width: 767px) {
            .map-management-container {
                padding: 15px;
                margin-left: 0;
            }

            .management-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .page-header {
                width: 100%;
            }

            .action-buttons {
                flex-direction: column;
                width: 100%;
            }

            .btn-edit,
            .btn-toggle,
            .btn-delete {
                width: 100%;
                justify-content: center;
            }

            .rooms-table {
                display: block;
                overflow-x: auto;
            }
        }

        @media (min-width: 768px) and (max-width: 1023px) {
            .map-management-container {
                padding: 20px 25px;
            }
        }

        /* Dark mode support */
        body.dark-mode .map-management-container,
        body.dark-mode .map-viewer-section,
        body.dark-mode .rooms-table-container {
            background: #263645;
            border-color: #121A21;
            color: #E5E8EB;
        }

        body.dark-mode .map-viewer-header,
        body.dark-mode .table-header {
            background: #121A21;
            border-color: #263645;
        }

        body.dark-mode .form-group input,
        body.dark-mode .form-group select,
        body.dark-mode .form-group textarea {
            background: #121A21;
            border-color: #263645;
            color: #E5E8EB;
        }

        body.dark-mode .rooms-table {
            background: #263645;
            color: #E5E8EB;
        }

        body.dark-mode .rooms-table th,
        body.dark-mode .rooms-table td {
            border-color: #121A21;
            color: #E5E8EB;
        }

        body.dark-mode .rooms-table tr:hover {
            background: #1C7DD6;
        }




        /* ====================================================================== */
        /* Dark Mode Overrides for Map Management Page - Custom Colors           */
        /* ====================================================================== */
        body.dark-mode {
            background-color: #121A21 !important;
            /* Primary dark background */
            color: #E5E8EB !important;
            /* Primary light text */
        }

        /* Main layout containers */
        body.dark-mode .map-management-main-container {
            background-color: #121A21 !important;
        }

        body.dark-mode .map-management-container {
            background-color: #121A21 !important;
            color: #E5E8EB !important;
        }

        /* Header section */
        body.dark-mode .management-header {
            background-color: transparent !important;
            border-bottom: 1px solid #263645 !important;
            /* Secondary dark border */
        }

        body.dark-mode .page-title h1 {
            color: #E5E8EB !important;
            /* Light text for page title */
        }

        body.dark-mode .page-title .subtitle {
            color: #94ADC7 !important;
            /* Secondary text for subtitle */
        }

        /* Back button */
        body.dark-mode .btn-back {
            background: #263645 !important;
            /* Secondary dark background */
            color: #E5E8EB !important;
            border: 1px solid #121A21 !important;
        }

        body.dark-mode .btn-back:hover {
            background: #1C7DD6 !important;
            /* Active blue on hover */
            color: #FFFFFF !important;
            border-color: #1C7DD6 !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3) !important;
        }

        /* Add Room button */
        body.dark-mode .btn-add-room {
            background: #1C7DD6 !important;
            /* Active blue */
            color: #FFFFFF !important;
            border: none !important;
        }

        body.dark-mode .btn-add-room:hover {
            background: #1565C0 !important;
            /* Darker blue on hover */
            box-shadow: 0 4px 12px rgba(28, 125, 214, 0.3) !important;
        }

        /* Map Viewer Section */
        body.dark-mode .map-viewer-section {
            background-color: #263645 !important;
            /* Secondary dark background */
            border: 1px solid #121A21 !important;
            color: #E5E8EB !important;
        }

        body.dark-mode .map-viewer-header {
            background-color: #121A21 !important;
            /* Primary dark */
            border-bottom: 1px solid #263645 !important;
            color: #E5E8EB !important;
        }

        body.dark-mode .map-viewer-header h5 {
            color: #E5E8EB !important;
        }

        body.dark-mode .map-viewer-header h5 i {
            color: #1C7DD6 !important;
            /* Active blue for icon */
        }

        /* Zoom controls */
        body.dark-mode .zoom-btn {
            background-color: #263645 !important;
            /* Secondary dark */
            border: 1px solid #121A21 !important;
            color: #E5E8EB !important;
        }

        body.dark-mode .zoom-btn:hover {
            background-color: #1C7DD6 !important;
            /* Active blue on hover */
            border-color: #1C7DD6 !important;
            color: #FFFFFF !important;
        }

        /* Map container */
        body.dark-mode .map-display-container {
            background-color: #121A21 !important;
        }

        body.dark-mode #mapContainer {
            background-color: #263645 !important;
            border: 1px solid #121A21 !important;
        }

        body.dark-mode .map-info-panel {
            background-color: #121A21 !important;
            /* Primary dark */
            border-top: 1px solid #263645 !important;
            color: #94ADC7 !important;
            /* Secondary text */
        }

        /* Rooms Table Container */
        body.dark-mode .rooms-table-container {
            background-color: #263645 !important;
            /* Secondary dark */
            border: 1px solid #121A21 !important;
        }

        body.dark-mode .table-header {
            background-color: #121A21 !important;
            border-bottom: 1px solid #263645 !important;
        }

        body.dark-mode .table-header h3 {
            color: #E5E8EB !important;
        }

        body.dark-mode .table-header h3 i {
            color: #1C7DD6 !important;
            /* Active blue for icon */
        }

        /* Table styling */
        body.dark-mode .rooms-table {
            background-color: #263645 !important;
            color: #E5E8EB !important;
        }

        body.dark-mode .rooms-table thead {
            background-color: #121A21 !important;
            border-bottom: 2px solid #263645 !important;
        }

        body.dark-mode .rooms-table th {
            color: #E5E8EB !important;
            border-bottom: 1px solid #263645 !important;
            background-color: #121A21 !important;
        }

        body.dark-mode .rooms-table td {
            color: #E5E8EB !important;
            border-bottom: 1px solid #263645 !important;
            background-color: transparent !important;
        }

        body.dark-mode .rooms-table tr:hover {
            background-color: rgba(28, 125, 214, 0.2) !important;
            /* Semi-transparent blue on hover */
        }

        body.dark-mode .rooms-table tr:last-child td {
            border-bottom: none !important;
        }

        /* Status badges */
        body.dark-mode .status-available {
            background: #1B5E20 !important;
            /* Dark green */
            color: #C8E6C9 !important;
            /* Light green text */
        }

        body.dark-mode .status-unavailable {
            background: #B71C1C !important;
            /* Dark red */
            color: #FFCDD2 !important;
            /* Light red text */
        }

        /* Action buttons */
        body.dark-mode .btn-edit {
            background-color: #1C7DD6 !important;
            /* Active blue */
            color: #FFFFFF !important;
            border: none !important;
        }

        body.dark-mode .btn-edit:hover {
            background-color: #1565C0 !important;
            /* Darker blue on hover */
            box-shadow: 0 4px 8px rgba(28, 125, 214, 0.3) !important;
        }

        body.dark-mode .btn-toggle {
            background-color: #F57C00 !important;
            /* Dark orange */
            color: #FFFFFF !important;
            border: none !important;
        }

        body.dark-mode .btn-toggle:hover {
            background-color: #EF6C00 !important;
            /* Darker orange on hover */
            box-shadow: 0 4px 8px rgba(245, 124, 0, 0.3) !important;
        }

        body.dark-mode .btn-delete {
            background-color: #C62828 !important;
            /* Dark red */
            color: #FFFFFF !important;
            border: none !important;
        }

        body.dark-mode .btn-delete:hover {
            background-color: #B71C1C !important;
            /* Darker red on hover */
            box-shadow: 0 4px 8px rgba(198, 40, 40, 0.3) !important;
        }

        /* Modal Styles */
        body.dark-mode .modal {
            background: rgba(0, 0, 0, 0.7) !important;
            /* Darker overlay */
        }

        body.dark-mode .modal-content {
            background-color: #263645 !important;
            /* Secondary dark background */
            border: 1px solid #121A21 !important;
            color: #E5E8EB !important;
        }

        body.dark-mode .modal-header {
            background-color: #121A21 !important;
            border-bottom: 1px solid #263645 !important;
        }

        body.dark-mode .modal-header h3 {
            color: #E5E8EB !important;
        }

        body.dark-mode .modal-header h3 i {
            color: #1C7DD6 !important;
            /* Active blue for icon */
        }

        body.dark-mode .modal-close {
            color: #94ADC7 !important;
            /* Secondary text color */
        }

        body.dark-mode .modal-close:hover {
            color: #E5E8EB !important;
            /* Light text on hover */
        }

        /* Form elements */
        body.dark-mode .form-group label {
            color: #E5E8EB !important;
            /* Light text for labels */
        }

        body.dark-mode .form-group input,
        body.dark-mode .form-group select,
        body.dark-mode .form-group textarea {
            background-color: #121A21 !important;
            /* Primary dark */
            border: 1px solid #263645 !important;
            /* Secondary border */
            color: #E5E8EB !important;
            /* Light text */
        }

        body.dark-mode .form-group input:focus,
        body.dark-mode .form-group select:focus,
        body.dark-mode .form-group textarea:focus {
            background-color: #121A21 !important;
            border-color: #1C7DD6 !important;
            /* Blue focus */
            color: #E5E8EB !important;
            box-shadow: 0 0 0 3px rgba(28, 125, 214, 0.2) !important;
        }

        /* Checkbox */
        body.dark-mode .checkbox-group input[type="checkbox"] {
            filter: brightness(0.8) contrast(1.2);
        }

        body.dark-mode .checkbox-group label {
            color: #E5E8EB !important;
        }

        /* Modal buttons */
        body.dark-mode .btn-save {
            background-color: #1C7DD6 !important;
            /* Active blue */
            color: #FFFFFF !important;
            border: none !important;
        }

        body.dark-mode .btn-save:hover {
            background-color: #1565C0 !important;
            /* Darker blue on hover */
            box-shadow: 0 4px 12px rgba(28, 125, 214, 0.3) !important;
        }

        body.dark-mode .btn-cancel {
            background-color: #263645 !important;
            /* Secondary dark */
            color: #E5E8EB !important;
            border: 1px solid #121A21 !important;
        }

        body.dark-mode .btn-cancel:hover {
            background-color: #121A21 !important;
            /* Primary dark on hover */
            color: #E5E8EB !important;
            border-color: #263645 !important;
        }

        /* Alert Styles */
        body.dark-mode .alert {
            background-color: #263645 !important;
            /* Secondary dark background */
            border: 1px solid #121A21 !important;
        }

        body.dark-mode .alert-success {
            background-color: #1B5E20 !important;
            /* Dark green */
            color: #C8E6C9 !important;
            /* Light green text */
            border-color: #2E7D32 !important;
        }

        body.dark-mode .alert-error {
            background-color: #B71C1C !important;
            /* Dark red */
            color: #FFCDD2 !important;
            /* Light red text */
            border-color: #C62828 !important;
        }

        /* Scrollbar Styling for Dark Mode */
        body.dark-mode ::-webkit-scrollbar {
            width: 12px;
            height: 12px;
        }

        body.dark-mode ::-webkit-scrollbar-track {
            background: #121A21 !important;
            /* Primary dark track */
        }

        body.dark-mode ::-webkit-scrollbar-thumb {
            background-color: #263645 !important;
            /* Secondary dark thumb */
            border-radius: 6px;
            border: 3px solid #121A21 !important;
        }

        body.dark-mode ::-webkit-scrollbar-thumb:hover {
            background-color: #1C7DD6 !important;
            /* Blue on hover */
        }

        /* Map container scrollbar specific */
        body.dark-mode #mapContainer::-webkit-scrollbar-track {
            background: #121A21 !important;
        }

        body.dark-mode #mapContainer::-webkit-scrollbar-thumb {
            background-color: #263645 !important;
            border: 2px solid #121A21 !important;
        }

        body.dark-mode #mapContainer::-webkit-scrollbar-thumb:hover {
            background-color: #1C7DD6 !important;
        }

        /* For Firefox dark mode */
        body.dark-mode #mapContainer {
            scrollbar-color: #263645 #121A21 !important;
        }

        /* Responsive adjustments for dark mode */
        @media (max-width: 767px) {
            body.dark-mode .map-management-container {
                background-color: #121A21 !important;
            }

            body.dark-mode .management-header {
                background-color: #121A21 !important;
            }
        }

        @media (min-width: 768px) and (max-width: 1023px) {
            body.dark-mode .map-management-container {
                background-color: #121A21 !important;
            }
        }

        @media (min-width: 1024px) {
            body.dark-mode .map-management-container {
                background-color: #121A21 !important;
            }
        }

        /* Icon colors in dark mode */
        body.dark-mode .fa-map,
        body.dark-mode .fa-building,
        body.dark-mode .fa-door-open {
            color: #1C7DD6 !important;
            /* Active blue for icons */
        }

        body.dark-mode .status-available i,
        body.dark-mode .alert-success i {
            color: #C8E6C9 !important;
            /* Light green for success icons */
        }

        body.dark-mode .status-unavailable i,
        body.dark-mode .alert-error i {
            color: #FFCDD2 !important;
            /* Light red for error icons */
        }

        /* Hover effects for dark mode */
        body.dark-mode .btn-add-room:hover i,
        body.dark-mode .btn-edit:hover i,
        body.dark-mode .btn-save:hover i {
            color: #FFFFFF !important;
            /* White icons on hover for primary buttons */
        }

        /* Map info panel icon */
        body.dark-mode .map-info-panel i {
            color: #1C7DD6 !important;
            /* Active blue for info icon */
        }

        /* Action button icons in table */
        body.dark-mode .btn-toggle i,
        body.dark-mode .btn-delete i {
            color: #FFFFFF !important;
            /* White icons for action buttons */
        }

        /* Disabled states in dark mode (if needed) */
        body.dark-mode input:disabled,
        body.dark-mode select:disabled,
        body.dark-mode textarea:disabled {
            background-color: #263645 !important;
            color: #94ADC7 !important;
            cursor: not-allowed;
        }

        /* Placeholder text in dark mode */
        body.dark-mode ::placeholder {
            color: #94ADC7 !important;
            /* Secondary text for placeholders */
        }

        body.dark-mode ::-webkit-input-placeholder {
            color: #94ADC7 !important;
        }

        body.dark-mode ::-moz-placeholder {
            color: #94ADC7 !important;
        }

        body.dark-mode :-ms-input-placeholder {
            color: #94ADC7 !important;
        }
    </style>
</head>

<body>
    <?php
    $sidenav_path = '../../templates/admin/sidenav_admin.php';
    require_once $sidenav_path;
    ?>

    <div class="map-management-main-container">
        <div class="map-management-container">
            <!-- Page Header -->
            <div class="management-header">
                <div class="page-header">
                    <a href="../../pages/admin/dashboard.php" class="btn-back" title="Back to Dashboard">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <div class="page-title">
                        <h1>Campus Map Management</h1>
                        <div class="subtitle">Manage room availability and details for campus navigation</div>
                    </div>
                </div>

                <button class="btn-add-room" onclick="openAddRoomModal()">
                    <i class="fas fa-plus-circle"></i>
                    <span>Add New Room</span>
                </button>
            </div>

            <!-- Alert Container -->
            <div id="alertContainer"></div>

            <!-- Campus Map Viewer -->
            <div class="map-viewer-section">
                <div class="map-viewer-header">
                    <h5><i class="fas fa-map"></i> Campus Map Overview</h5>
                    <div class="zoom-controls">
                        <button class="zoom-btn" onclick="zoomIn()" title="Zoom In">
                            <i class="fas fa-plus"></i>
                        </button>
                        <button class="zoom-btn" onclick="zoomOut()" title="Zoom Out">
                            <i class="fas fa-minus"></i>
                        </button>
                        <button class="zoom-btn" onclick="resetZoom()" title="Reset Zoom">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </div>

                <div class="map-display-container">
                    <div id="mapContainer">
                        <iframe id="campusMap" src="../../assets/img/UC-MAIN-UPDATED%20(WITH%20DIMENSIONS)-1.svg"
                            title="University Campus Map" loading="lazy" referrerpolicy="no-referrer">
                        </iframe>
                    </div>
                </div>

                <div class="map-info-panel">
                    <i class="fas fa-info-circle me-1"></i>
                    <span>Use zoom buttons or scroll with mouse to navigate. Click on rooms or manage them in the table
                        below.</span>
                </div>
            </div>

            <!-- Rooms Management Table -->
            <div class="rooms-table-container">
                <div class="table-header">
                    <h3><i class="fas fa-building"></i> Room Management</h3>
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
                                    <span
                                        class="status-badge <?= $room['is_available'] ? 'status-available' : 'status-unavailable' ?>">
                                        <i
                                            class="fas fa-<?= $room['is_available'] ? 'check-circle' : 'times-circle' ?>"></i>
                                        <?= $room['is_available'] ? 'Available' : 'Unavailable' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-edit" onclick="openEditRoomModal(<?= $room['id'] ?>)">
                                            <i class="fas fa-edit"></i>
                                            <span>Edit</span>
                                        </button>
                                        <button class="btn-toggle"
                                            onclick="toggleRoomStatus(<?= $room['id'] ?>, <?= $room['is_available'] ? 1 : 0 ?>)">
                                            <i class="fas fa-exchange-alt"></i>
                                            <span><?= $room['is_available'] ? 'Mark Unavailable' : 'Mark Available' ?></span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add/Edit Room Modal -->
    <div id="roomModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-door-open"></i> <span id="modalTitle">Add New Room</span></h3>
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
                    <textarea id="location" name="location_description"
                        placeholder="e.g., 3rd Floor, Main Building, Wing A"></textarea>
                </div>

                <div class="form-group checkbox-group">
                    <input type="checkbox" id="isAvailable" name="is_available" value="1">
                    <label for="isAvailable">Mark as Available</label>
                </div>

                <div class="modal-buttons">
                    <button type="button" class="btn-cancel" onclick="closeRoomModal()">Cancel</button>
                    <button type="submit" class="btn-save">Save Room</button>
                </div>
            </form>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="../../assets/js/jquery.min.js"></script>

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
            const icon = type === 'success' ? 'check-circle' : 'exclamation-circle';

            alertContainer.innerHTML = `
                <div class="alert ${alertClass}">
                    <i class="fas fa-${icon}"></i>
                    <span>${message}</span>
                </div>
            `;

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
        document.getElementById('mapContainer').addEventListener('wheel', function (e) {
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
        document.getElementById('roomModal').addEventListener('click', function (e) {
            if (e.target === this) {
                closeRoomModal();
            }
        });
    </script>

    <!-- Favicon Script -->
    <script>
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
</body>

</html>

<script>
    document.body.style.backgroundColor = "#ffffff";
</script>