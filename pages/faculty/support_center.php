<?php
// CHRONONAV_WEB_DOSS/pages/faculty/support_center.php
// Faculty support center page - allows faculty to ask questions and get support

require_once '../../config/db_connect.php';
require_once '../../middleware/auth_check_faculty.php';

$page_title = "Support and Ask Question";
$current_page = "support_center";

// Handle form submission for support tickets
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    if ($_POST['action'] === 'create_ticket') {
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');
        
        $user_id = $_SESSION['user']['id'];
        
        // Validation
        $errors = [];
        if (empty($subject)) $errors[] = "Subject is required.";
        if (empty($message)) $errors[] = "Message is required.";
        
        if (empty($errors)) {
            $stmt = $conn->prepare(
                "INSERT INTO tickets (user_id, subject, message, status, created_at) 
                 VALUES (?, ?, ?, 'open', NOW())"
            );
            $stmt->bind_param("iss", $user_id, $subject, $message);
            
            if ($stmt->execute()) {
                $message_text = "Support ticket created successfully!";
                $message_type = "success";
                // Clear form
                $subject = $message = "";
            } else {
                $message_text = "Error creating ticket: " . $stmt->error;
                $message_type = "danger";
            }
            $stmt->close();
        } else {
            $message_text = "Error: " . implode(" ", $errors);
            $message_type = "danger";
        }
    }
}

// Fetch user's support tickets
$user_id = $_SESSION['user']['id'];
$tickets = [];
$result = $conn->query(
    "SELECT id, subject, status, created_at FROM tickets 
     WHERE user_id = $user_id ORDER BY created_at DESC"
);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $tickets[] = $row;
    }
}

require_once '../../templates/faculty/header_faculty.php';
require_once '../../templates/faculty/sidenav_faculty.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<div class="main-content-wrapper">
    <div class="container-fluid py-4">
        <h2 class="mb-4"><i class="fas fa-headset"></i> Support and Ask Question</h2>

        <?php if (isset($message_text)): ?>
            <div class="alert alert-<?= htmlspecialchars($message_type) ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message_text) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Create Support Ticket Form -->
            <div class="col-lg-5">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-plus-circle"></i> Create Support Ticket</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="create_ticket">
                            
                            <div class="mb-3">
                                <label for="subject" class="form-label fw-bold">Subject</label>
                                <input type="text" class="form-control" id="subject" name="subject" required 
                                    placeholder="Brief description of your issue">
                            </div>

                            <div class="mb-3">
                                <label for="message" class="form-label fw-bold">Detailed Message</label>
                                <textarea class="form-control" id="message" name="message" rows="6" required 
                                    placeholder="Please describe your issue in detail"></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-paper-plane"></i> Submit Ticket
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- My Support Tickets -->
            <div class="col-lg-7">
                <div class="card shadow-sm">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-list"></i> My Support Tickets (<?= count($tickets) ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($tickets)): ?>
                            <div class="alert alert-info" role="alert">
                                <i class="fas fa-info-circle"></i> No support tickets yet. Create one to get help!
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th><i class="fas fa-tag"></i> ID</th>
                                            <th><i class="fas fa-heading"></i> Subject</th>
                                            <th><i class="fas fa-check-circle"></i> Status</th>
                                            <th><i class="fas fa-calendar"></i> Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($tickets as $ticket): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($ticket['id']) ?></td>
                                                <td><?= htmlspecialchars($ticket['subject']) ?></td>
                                                <td>
                                                    <span class="badge bg-<?= $ticket['status'] === 'closed' ? 'success' : ($ticket['status'] === 'in progress' ? 'warning' : 'info') ?>">
                                                        <?= ucfirst($ticket['status']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $date = new DateTime($ticket['created_at']);
                                                    echo $date->format('M d, Y');
                                                    ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .main-content-wrapper {
        margin-left: 250px;
        padding: 20px;
        min-height: 100vh;
        background-color: #f8f9fa;
    }

    .card {
        border: none;
        border-radius: 0.5rem;
    }

    .card-header {
        border-bottom: 2px solid rgba(0, 0, 0, 0.1);
        padding: 1rem 1.5rem;
    }

    .card-body {
        padding: 1.5rem;
    }

    .form-label {
        margin-bottom: 0.5rem;
        font-size: 0.95rem;
    }

    .form-control, .form-select {
        border-radius: 0.375rem;
        border: 1px solid #dee2e6;
        padding: 0.75rem;
        font-size: 0.95rem;
    }

    .form-control:focus, .form-select:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }

    .table-hover tbody tr:hover {
        background-color: #f5f5f5;
    }

    .btn {
        padding: 0.75rem 1.5rem;
        border-radius: 0.375rem;
        font-weight: 500;
    }

    h2 {
        color: #333;
        margin-bottom: 1.5rem;
        font-weight: 600;
    }

    @media (max-width: 992px) {
        .main-content-wrapper {
            margin-left: 0;
        }
    }
</style>

<?php require_once '../../templates/footer.php'; ?>
