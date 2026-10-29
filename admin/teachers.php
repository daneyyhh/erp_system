<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
require_once('../config/db.php');

$page_title = "Faculty Management | Project ERP";
$success_msg = '';
$error_msg = '';

// Handle Add Teacher
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = password_hash($_POST['password'] ?? 'faculty123', PASSWORD_DEFAULT);
    $department = $_POST['department'] ?? '';
    $subject = $_POST['subject'] ?? '';

    try {
        $pdo->beginTransaction();
        
        // Insert into users
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'teacher')");
        $stmt->execute([$name, $email, $password]);
        $user_id = $pdo->lastInsertId();
        
        // Insert into teachers
        $stmt = $pdo->prepare("INSERT INTO teachers (user_id, subject, department) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $subject, $department]);
        
        $pdo->commit();
        $success_msg = "Faculty member added successfully!";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error_msg = "Error adding faculty. Email might already exist. " . $e->getMessage();
    }
}

// Handle Delete Teacher
if (isset($_GET['delete_id'])) {
    try {
        // user table cascade deletes the teacher table record
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'teacher'");
        $stmt->execute([$_GET['delete_id']]);
        $success_msg = "Faculty member removed completely.";
    } catch (PDOException $e) {
        $error_msg = "Error deleting faculty.";
    }
}

// Fetch Teachers
$teachers = $pdo->query("SELECT u.id as user_id, u.name, u.email, t.department, t.subject 
                         FROM users u 
                         JOIN teachers t ON u.id = t.user_id 
                         ORDER BY u.name ASC")->fetchAll();
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
                        <h2 class="fw-800 mb-1">Faculty Management</h2>
                        <p class="text-muted small fw-600">Onboard and manage teaching staff</p>
                    </div>
                    <button class="btn btn-primary px-4 py-3 shadow-sm d-flex align-items-center gap-2" style="border-radius: 16px; font-weight: 800;" data-bs-toggle="modal" data-bs-target="#addFacultyModal">
                        <i class="fas fa-user-plus"></i> Onboard Faculty
                    </button>
                </div>

                <div class="card-premium animate-up shadow-lg border-0">
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Faculty Profile</th>
                                    <th>Department</th>
                                    <th>Primary Subject</th>
                                    <th>Access Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($teachers)): ?>
                                    <tr><td colspan="5" class="text-center py-5 text-muted small">No faculty members found.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($teachers as $t): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="position-relative">
                                                <div class="d-flex align-items-center justify-content-center fw-900 fs-5 rounded-4 border" style="width: 45px; height: 45px; background: var(--primary-soft); color: var(--primary);">
                                                    <?php echo substr($t['name'], 0, 1); ?>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="fw-800 text-main mb-0"><?php echo htmlspecialchars($t['name']); ?></div>
                                                <div class="smallest text-muted fw-bold"><?php echo htmlspecialchars($t['email']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-800 small"><?php echo htmlspecialchars($t['department'] ?: 'Unassigned'); ?></div>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary bg-opacity-10 text-primary fw-900 uppercase ls-1 px-3 py-2 rounded-3" style="font-size: 0.65rem;">
                                            <?php echo htmlspecialchars($t['subject'] ?: 'General'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="spinner-grow spinner-grow-sm text-success" role="status" style="--bs-spinner-width: 0.5rem; --bs-spinner-height: 0.5rem;"></div>
                                            <span class="smallest fw-900 text-success uppercase ls-1">Active</span>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <a href="teachers.php?delete_id=<?php echo $t['user_id']; ?>" onclick="return confirm('Are you sure you want to remove this faculty member?');" class="btn btn-light btn-sm rounded-3 border px-3 fw-800 text-danger shadow-sm">
                                            <i class="fas fa-trash-alt"></i> Revoke
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

    <!-- Add Faculty Modal -->
    <div class="modal fade" id="addFacultyModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: var(--radius-xl); backdrop-filter: blur(20px);">
                <div class="modal-header border-0 pb-0 px-4 pt-4">
                    <h5 class="fw-900 mb-0">Onboard New Faculty</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form method="POST">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control" required placeholder="e.g. Dr. Sarah Jenkins">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" required placeholder="sarah@scholarly.com">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Department</label>
                            <input type="text" name="department" class="form-control" required placeholder="e.g. Computer Science">
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Primary Subject</label>
                            <input type="text" name="subject" class="form-control" required placeholder="e.g. Machine Learning">
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 py-3 fw-900 rounded-3">
                            <i class="fas fa-paper-plane me-2"></i> Send Onboarding Invite
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
