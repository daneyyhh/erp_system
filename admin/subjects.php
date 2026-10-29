<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
require_once('../config/db.php');

$page_title = "Course Manager | Project ERP";
$success_msg = '';
$error_msg = '';

// Handle Add Subject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = $_POST['name'] ?? '';
    $code = $_POST['code'] ?? '';
    $department = $_POST['department'] ?? '';
    $semester = $_POST['semester'] ?? 1;

    try {
        $stmt = $pdo->prepare("INSERT INTO subjects (name, code, department, semester) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $code, $department, $semester]);
        $success_msg = "Course curriculum added successfully!";
    } catch (PDOException $e) {
        $error_msg = "Error adding course. Code might already exist.";
    }
}

// Handle Delete Subject
if (isset($_GET['delete_id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM subjects WHERE id = ?");
        $stmt->execute([$_GET['delete_id']]);
        $success_msg = "Course completely removed.";
    } catch (PDOException $e) {
        $error_msg = "Cannot delete course because it is linked to faculty or exams.";
    }
}

$subjects = $pdo->query("SELECT * FROM subjects ORDER BY department ASC, semester ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="wrapper">
        <?php include('../includes/sidebar.php'); ?>
        <div class="main-content">
            <?php include('../includes/topbar.php'); ?>
            <div class="content-area">
                
                <?php if($success_msg): ?>
                    <div class="alert alert-success animate-up mb-4"><i class="fas fa-check-circle me-2"></i> <?php echo $success_msg; ?></div>
                <?php endif; ?>
                <?php if($error_msg): ?>
                    <div class="alert alert-danger animate-up mb-4"><i class="fas fa-exclamation-triangle me-2"></i> <?php echo $error_msg; ?></div>
                <?php endif; ?>

                <div class="d-flex justify-content-between align-items-center mb-5 animate-up">
                    <div>
                        <h2 class="fw-800 mb-1">Course Catalog</h2>
                        <p class="text-muted small fw-600">Master repository of subjects & curricula</p>
                    </div>
                    <button class="btn btn-warning px-4 py-3 shadow-sm d-flex align-items-center gap-2 text-white" style="border-radius: 16px; font-weight: 800;" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
                        <i class="fas fa-book-medical"></i> Map New Course
                    </button>
                </div>

                <div class="card-premium animate-up shadow-lg border-0">
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Course Code</th>
                                    <th>Curriculum Title</th>
                                    <th>Department</th>
                                    <th>Designated Sem</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($subjects)): ?>
                                    <tr><td colspan="5" class="text-center py-5 text-muted small">No courses currently mapped.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($subjects as $s): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-warning text-white fw-900 uppercase ls-1 px-3 py-2 rounded-3 shadow-sm" style="font-size: 0.75rem;">
                                            <?php echo htmlspecialchars($s['code']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-800 text-main"><?php echo htmlspecialchars($s['name']); ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-800 small text-muted"><?php echo htmlspecialchars($s['department']); ?></div>
                                    </td>
                                    <td>
                                        <span class="smallest fw-900 border border-2 border-primary text-primary px-3 py-1 rounded-pill bg-primary-soft">
                                            SEM <?php echo $s['semester']; ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="subjects.php?delete_id=<?php echo $s['id']; ?>" onclick="return confirm('Are you sure you want to drop this course from the catalog?');" class="btn btn-light btn-sm rounded-3 border px-3 fw-800 text-danger shadow-sm">
                                            <i class="fas fa-trash-alt"></i> Drop
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Add Subject Modal -->
    <div class="modal fade" id="addSubjectModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: var(--radius-xl); backdrop-filter: blur(20px);">
                <div class="modal-header border-0 pb-0 px-4 pt-4">
                    <h5 class="fw-900 mb-0">Map New Course</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form method="POST">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="mb-3">
                            <label class="form-label">Course Title</label>
                            <input type="text" name="name" class="form-control" required placeholder="e.g. Advanced AI">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Course Code</label>
                            <input type="text" name="code" class="form-control" required placeholder="e.g. CS401">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Associated Department</label>
                            <input type="text" name="department" class="form-control" required placeholder="e.g. Computer Science">
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Designated Semester</label>
                            <input type="number" name="semester" min="1" max="10" class="form-control" required placeholder="e.g. 5">
                        </div>
                        
                        <button type="submit" class="btn btn-warning text-white w-100 py-3 fw-900 rounded-3 shadow-sm">
                            <i class="fas fa-plus-circle me-2"></i> Map to Catalog
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
