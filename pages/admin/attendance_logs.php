<?php
// CHRONONAV_WEB_DOSS/pages/faculty/attendance_logs.php
require_once '../../middleware/auth_check.php';
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

// Change to faculty role check
requireRole(['faculty', 'admin']); 

$user = $_SESSION['user'];
$current_user_id = $user['id'];

// --- Fetch fresh user data ---
$stmt_user = $conn->prepare("SELECT name, email, profile_img, role FROM users WHERE id = ?");
if ($stmt_user) {
    $stmt_user->bind_param("i", $current_user_id);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    if ($result_user->num_rows > 0) {
        $user_data = $result_user->fetch_assoc();
        $display_username = htmlspecialchars($user_data['name'] ?? 'User');
        $display_user_role = htmlspecialchars(ucfirst($user_data['role'] ?? 'Faculty'));
        $profile_img_src = (strpos($user_data['profile_img'] ?? '', 'uploads/') === 0) ? '../../' . htmlspecialchars($user_data['profile_img']) : '../../uploads/profiles/default-avatar.png';
    }
    $stmt_user->close();
}

$page_title = "My Class Attendance Logs";
$current_page = "attendance_logs";

// --- Fetch ONLY Classes belonging to this Faculty ---
$classes = [];
$stmt_classes = $conn->prepare("
    SELECT c.class_id, c.class_name, c.class_code, c.semester, c.academic_year
    FROM classes c
    WHERE c.faculty_id = ?
    ORDER BY c.academic_year DESC, c.semester DESC, c.class_name ASC
");

if ($stmt_classes) {
    $stmt_classes->bind_param("i", $current_user_id);
    $stmt_classes->execute();
    $result_classes = $stmt_classes->get_result();
    while ($row = $result_classes->fetch_assoc()) {
        $classes[] = $row;
    }
    $stmt_classes->close();
}

$class_attendance_data = [];
foreach ($classes as $class) {
    $class_id = $class['class_id'];
    $class_attendance_data[$class_id] = ['info' => $class, 'sessions' => []];

    // Get sessions for this class
    $stmt_sessions = $conn->prepare("
        SELECT cs.id AS session_id, cs.session_date, cs.actual_start_time, cs.actual_end_time, r.room_name
        FROM class_sessions cs
        LEFT JOIN rooms r ON cs.room_id = r.id
        WHERE cs.class_id = ?
        ORDER BY cs.session_date DESC
    ");
    $stmt_sessions->bind_param("i", $class_id);
    $stmt_sessions->execute();
    $res_sessions = $stmt_sessions->get_result();

    while ($session = $res_sessions->fetch_assoc()) {
        $session_id = $session['session_id'];
        
        // CRITICAL FIX: Join with class_students so ALL enrolled students show up
        $stmt_att = $conn->prepare("
            SELECT 
                u.id AS student_id, 
                u.name AS student_name, 
                u.student_id AS student_school_id,
                ar.status
            FROM class_students cs_list
            JOIN users u ON cs_list.student_id = u.id
            LEFT JOIN attendance_records ar ON ar.student_id = u.id AND ar.session_id = ?
            WHERE cs_list.class_id = ?
            ORDER BY u.name ASC
        ");
        $stmt_att->bind_param("ii", $session_id, $class_id);
        $stmt_att->execute();
        $attendance_records = $stmt_att->get_result()->fetch_all(MYSQLI_ASSOC);
        
        $class_attendance_data[$class_id]['sessions'][$session_id] = [
            'info' => $session,
            'attendance_records' => $attendance_records
        ];
        $stmt_att->close();
    }
    $stmt_sessions->close();
}

require_once '../../templates/faculty/header_faculty.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $page_title ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .btn-attendance { width: 90px; font-size: 0.75rem; font-weight: 600; }
        /* Active States */
        .status-active-present { background-color: #198754 !important; color: white !important; border-color: #198754 !important; }
        .status-active-late { background-color: #ffc107 !important; color: black !important; border-color: #ffc107 !important; }
        .status-active-absent { background-color: #dc3545 !important; color: white !important; border-color: #dc3545 !important; }
        
        .session-card { border-left: 5px solid #0d6efd; margin-bottom: 20px; }
        .student-row:hover { background-color: #f8f9fa; }
    </style>
</head>
<body class="bg-light">
    <?php require_once '../../templates/faculty/sidenav_faculty.php'; ?>

    <div class="main-content-wrapper">
        <div class="container-fluid p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold m-0"><?= $page_title ?></h2>
            </div>

            <?php if (empty($classes)): ?>
                <div class="alert alert-info">You have no assigned classes.</div>
            <?php else: ?>
                <?php foreach ($class_attendance_data as $class_id => $data): ?>
                    <div class="card shadow-sm mb-5">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-chalkboard-teacher me-2"></i><?= htmlspecialchars($data['info']['class_name']) ?> (<?= htmlspecialchars($data['info']['class_code']) ?>)</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($data['sessions'])): ?>
                                <div class="text-center py-3 text-muted">No sessions created for this class yet.</div>
                            <?php endif; ?>

                            <?php foreach ($data['sessions'] as $session_id => $s_data): ?>
                                <div class="card session-card shadow-none border">
                                    <div class="card-header bg-light d-flex justify-content-between">
                                        <span class="fw-bold">
                                            <i class="fas fa-calendar-day me-2"></i><?= date('M d, Y', strtotime($s_data['info']['session_date'])) ?> 
                                            <span class="text-muted fw-normal ms-2">(<?= date('h:i A', strtotime($s_data['info']['actual_start_time'])) ?>)</span>
                                        </span>
                                        <span class="badge bg-secondary"><i class="fas fa-map-marker-alt me-1"></i><?= htmlspecialchars($s_data['info']['room_name'] ?? 'N/A') ?></span>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width: 40%;">Student Name</th>
                                                    <th class="text-center">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($s_data['attendance_records'] as $record): ?>
                                                    <tr class="student-row">
                                                        <td>
                                                            <div class="fw-bold text-dark"><?= htmlspecialchars($record['student_name']) ?></div>
                                                            <small class="text-muted"><?= htmlspecialchars($record['student_school_id'] ?? 'No ID') ?></small>
                                                        </td>
                                                        <td class="text-center">
                                                            <div class="btn-group" role="group">
                                                                <button type="button" 
                                                                    class="btn btn-outline-success btn-attendance mark-btn <?= ($record['status'] == 'Present') ? 'status-active-present' : '' ?>" 
                                                                    onclick="updateAttendance(<?= $session_id ?>, <?= $record['student_id'] ?>, 'Present', this)">Present</button>
                                                                
                                                                <button type="button" 
                                                                    class="btn btn-outline-warning btn-attendance mark-btn <?= ($record['status'] == 'Late') ? 'status-active-late' : '' ?>" 
                                                                    onclick="updateAttendance(<?= $session_id ?>, <?= $record['student_id'] ?>, 'Late', this)">Late</button>
                                                                
                                                                <button type="button" 
                                                                    class="btn btn-outline-danger btn-attendance mark-btn <?= ($record['status'] == 'Absent') ? 'status-active-absent' : '' ?>" 
                                                                    onclick="updateAttendance(<?= $session_id ?>, <?= $record['student_id'] ?>, 'Absent', this)">Absent</button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="../../assets/js/jquery.min.js"></script>
    <script>
        function updateAttendance(sessionId, studentId, status, btn) {
            const $btn = $(btn);
            const $row = $btn.closest('tr');
            
            // Add a loading effect
            $btn.prop('disabled', true);

            $.ajax({
                url: '../../actions/faculty/update_attendance_ajax.php',
                method: 'POST',
                data: {
                    session_id: sessionId,
                    student_id: studentId,
                    status: status
                },
                dataType: 'json',
                success: function(response) {
                    $btn.prop('disabled', false);
                    if(response.success) {
                        // Reset all buttons in this row
                        $row.find('.mark-btn').removeClass('status-active-present status-active-late status-active-absent');
                        
                        // Apply active class based on choice
                        if(status === 'Present') $btn.addClass('status-active-present');
                        if(status === 'Late') $btn.addClass('status-active-late');
                        if(status === 'Absent') $btn.addClass('status-active-absent');
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    $btn.prop('disabled', false);
                    alert('Could not connect to server.');
                }
            });
        }
    </script>
</body>
</html>