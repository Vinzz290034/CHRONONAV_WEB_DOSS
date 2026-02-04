<?php
// CHRONONAV_WEB_DOSS/pages/admin/enroll_students.php
require_once '../../middleware/auth_check.php';
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

requireRole(['admin']);

$page_title = "Student Enrollment";
$current_page = "enrollment";

// Fetch all classes for the dropdown
$classes = $conn->query("SELECT class_id, class_name, class_code FROM classes ORDER BY class_name ASC")->fetch_all(MYSQLI_ASSOC);

// Fetch all users with the role 'user' (students)
$students = $conn->query("SELECT id, name, student_id FROM users WHERE role = 'user' ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

require_once '../../templates/admin/header_admin.php';
require_once '../../templates/admin/sidenav_admin.php';
?>

<div class="main-content-wrapper">
    <div class="container-fluid py-4">
        <h2 class="mb-4 fw-bold"><?= $page_title ?></h2>

        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Enroll Students to Class</h5>
            </div>
            <div class="card-body">
                <form id="enrollmentForm">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Select Class</label>
                            <select name="class_id" id="class_id" class="form-select" required>
                                <option value="">-- Choose a Class --</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?= $class['class_id'] ?>">
                                        <?= htmlspecialchars($class['class_name']) ?> (<?= htmlspecialchars($class['class_code']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Select Students</label>
                        <div class="table-responsive border rounded" style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th width="50"><input type="checkbox" id="selectAll"></th>
                                        <th>Student Name</th>
                                        <th>Student ID</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $student): ?>
                                        <tr>
                                            <td><input type="checkbox" name="student_ids[]" value="<?= $student['id'] ?>" class="student-checkbox"></td>
                                            <td><?= htmlspecialchars($student['name']) ?></td>
                                            <td><?= htmlspecialchars($student['student_id'] ?? 'N/A') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success px-4">
                        <i class="fas fa-user-plus me-2"></i> Enroll Selected Students
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="../../assets/js/jquery.min.js"></script>
<script>
$(document).ready(function() {
    // Select All functionality
    $('#selectAll').on('click', function() {
        $('.student-checkbox').prop('checked', this.checked);
    });

    $('#enrollmentForm').on('submit', function(e) {
        e.preventDefault();
        
        if (!$('#class_id').val()) {
            alert("Please select a class.");
            return;
        }

        if ($('.student-checkbox:checked').length === 0) {
            alert("Please select at least one student.");
            return;
        }

        $.ajax({
            url: '../../actions/admin/process_enrollment.php',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                if(res.success) {
                    alert("Enrollment successful!");
                    window.location.reload();
                } else {
                    alert("Error: " + res.message);
                }
            }
        });
    });
});
</script>

<?php include_once '../../templates/footer.php'; ?>