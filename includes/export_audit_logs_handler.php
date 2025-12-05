<?php
// CHRONONAV_WEB_DOSS/includes/export_audit_logs_handler.php
session_start();
require_once '../middleware/auth_check.php';
require_once '../config/db_connect.php';

// --- Security Check: Ensure user is admin ---
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    die("Access denied. Admin privileges required for log export.");
}

/** @var \mysqli $conn */

if (isset($_GET['export']) && $_GET['export'] == '1') {
    
    // 1. Capture and Sanitize Filtering Parameters
    $role_filter = $_GET['role_filter'] ?? '';
    $action_filter = $_GET['action_filter'] ?? '';
    $where_clauses = [];
    $bind_params = [];
    $bind_types = '';

    if (!empty($role_filter)) {
        $where_clauses[] = "u.role = ?";
        $bind_params[] = $role_filter;
        $bind_types .= 's';
    }

    if (!empty($action_filter)) {
        $where_clauses[] = "al.action = ?";
        $bind_params[] = $action_filter;
        $bind_types .= 's';
    }

    $where_sql = count($where_clauses) > 0 ? " WHERE " . implode(" AND ", $where_clauses) : "";

    // 2. Define SQL Query
    $sql_logs = "
        SELECT al.id, u.name AS user_name, u.role AS user_role, al.action, al.details, al.timestamp
        FROM audit_log al
        JOIN users u ON al.user_id = u.id
        {$where_sql}
        ORDER BY al.timestamp DESC
    ";

    $stmt_logs = $conn->prepare($sql_logs);

    if ($stmt_logs) {
        // 3. Dynamically Bind Parameters
        if (!empty($bind_params)) {
            $bind_names = array($bind_types);
            foreach ($bind_params as $key => $value) {
                $bind_names[] = &$bind_params[$key];
            }
            // Use call_user_func_array for dynamic binding in mysqli
            call_user_func_array([$stmt_logs, 'bind_param'], $bind_names);
        }
        
        $stmt_logs->execute();
        $result_logs = $stmt_logs->get_result();

        // 4. Set CSV Headers and Prepare Output
        $filename = "audit_logs_" . date('Ymd_His') . ".csv";
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        
        // Write CSV headers
        fputcsv($output, ['ID', 'User Name', 'User Role', 'Action', 'Details', 'Timestamp']);

        // 5. Write Data Rows
        while ($row = $result_logs->fetch_assoc()) {
            fputcsv($output, [
                $row['id'],
                $row['user_name'],
                $row['user_role'],
                $row['action'],
                $row['details'],
                $row['timestamp']
            ]);
        }

        $stmt_logs->close();
        fclose($output);
        exit();

    } else {
        error_log("Export logs prepare failed: " . $conn->error);
        die("Error generating export file due to a database query failure.");
    }
} else {
    die("Invalid export request.");
}
?>