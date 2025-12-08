<?php
require_once '../../middleware/auth_check.php';
require_once '../../config/db_connect.php';

if (!isset($_GET['schedule_id'])) {
    echo "<div class='alert alert-danger'>No schedule ID provided.</div>";
    exit;
}

$schedule_id = $_GET['schedule_id'];

$stmt = $conn->prepare("
    SELECT * FROM add_pdf 
    WHERE schedule_code = ? AND user_id = ?
");
$stmt->bind_param("ii", $schedule_id, $_SESSION['user']['id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<div class='alert alert-warning'>Schedule not found.</div>";
    exit;
}

$row = $result->fetch_assoc();
?>

<div class="p-3">
    <h4><?= htmlspecialchars($row['title']) ?></h4>

    <p><strong>Room:</strong> <?= htmlspecialchars($row['room']) ?></p>
    <p><strong>Day:</strong> <?= htmlspecialchars($row['day_of_week']) ?></p>
    <p><strong>Time:</strong> 
        <?= htmlspecialchars($row['start_time']) ?> - 
        <?= htmlspecialchars($row['end_time']) ?>
    </p>

    <p><strong>Schedule Code:</strong> <?= htmlspecialchars($row['schedule_code']) ?></p>
</div>
