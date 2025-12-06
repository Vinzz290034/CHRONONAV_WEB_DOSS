<?php
// CHRONONAV_WEB_DOSS/pages/faculty/announcements.php
// Faculty announcements page - view campus announcements

require_once '../../config/db_connect.php';
require_once '../../middleware/auth_check_faculty.php';

$page_title = "Campus Announcements";
$current_page = "announcements";

// Fetch announcements from database
$announcements = [];
$result = $conn->query(
    "SELECT id, title, content, user_id, published_at, updated_at FROM announcements 
     ORDER BY published_at DESC LIMIT 50"
);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $announcements[] = $row;
    }
}

require_once '../../templates/faculty/header_faculty.php';
require_once '../../templates/faculty/sidenav_faculty.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<div class="main-content-wrapper">
    <div class="container-fluid py-4">
        <h2 class="mb-4"><i class="fas fa-bullhorn"></i> Campus Announcements</h2>

        <div class="row">
            <!-- Announcements List -->
            <div class="col-lg-12">
                <?php if (empty($announcements)): ?>
                    <div class="card shadow-sm">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-inbox" style="font-size: 3rem; color: #ccc;"></i>
                            <h5 class="mt-3 text-muted">No announcements available</h5>
                            <p class="text-muted">Check back later for campus announcements</p>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($announcements as $announcement): ?>
                        <div class="card shadow-sm mb-3">
                            <div class="card-header bg-light border-bottom">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h5 class="mb-1"><?= htmlspecialchars($announcement['title']) ?></h5>
                                        <small class="text-muted">
                                            <i class="fas fa-calendar"></i>
                                            <?php 
                                            $date = new DateTime($announcement['published_at']);
                                            echo $date->format('M d, Y \a\t h:i A');
                                            ?>
                                        </small>
                                    </div>
                                    <?php if ($announcement['updated_at'] !== $announcement['published_at']): ?>
                                        <small class="text-warning">
                                            <i class="fas fa-edit"></i> Updated
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-body">
                                <p><?= nl2br(htmlspecialchars($announcement['content'])) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
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
        padding: 1rem 1.5rem;
    }

    .card-body {
        padding: 1.5rem;
    }

    .card-body p {
        color: #555;
        line-height: 1.6;
        margin-bottom: 0;
    }

    h2 {
        color: #333;
        margin-bottom: 1.5rem;
        font-weight: 600;
    }

    h5 {
        color: #333;
        font-weight: 600;
    }

    .badge {
        padding: 0.5rem 0.75rem;
        border-radius: 0.375rem;
    }

    @media (max-width: 992px) {
        .main-content-wrapper {
            margin-left: 0;
        }
    }
</style>

<?php require_once '../../templates/footer.php'; ?>
