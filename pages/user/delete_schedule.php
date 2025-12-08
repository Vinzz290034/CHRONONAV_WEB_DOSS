<?php
require_once '../../middleware/auth_check.php';
require_once '../../config/db_connect.php';

header("Content-Type: application/json");

if (!isset($_POST['schedule_id'])) {
    echo json_encode(["success" => false, "message" => "No schedule ID provided."]);
    exit;
}

$schedule_id = $_POST['schedule_id'];
$user_id = $_SESSION['user']['id'];

$stmt = $conn->prepare("
    DELETE FROM add_pdf 
    WHERE schedule_code = ? AND user_id = ?
");
$stmt->bind_param("ii", $schedule_id, $user_id);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Schedule deleted successfully."]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to delete schedule."]);
}
