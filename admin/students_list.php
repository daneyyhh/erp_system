<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
$current_role = $_SESSION['user_role'] ?? 'student';
// 'Can see everyone' policy active - no role-based filtering restrictions
require_once('../config/db.php');

$page_title = "Identity Manager | Project ERP";

// Filtering
$role_filter = $_GET['role'] ?? 'student'; // Default to student
$search = $_GET['search'] ?? '';

$sql = "";
if ($role_filter === 'student') {
    $sql = "SELECT u.id, u.name, u.email, 'Student' as display_role, s.roll_no as meta, s.class as subtitle 
            FROM users u JOIN students s ON u.id = s.user_id";
} elseif ($role_filter === 'teacher') {
    $sql = "SELECT u.id, u.name, u.email, 'Teacher' as display_role, t.department as meta, t.subject as subtitle 
            FROM users u JOIN teachers t ON u.id = t.user_id";
} elseif ($role_filter === 'parent') {
    $sql = "SELECT u.id, u.name, u.email, 'Parent' as display_role, 'Family' as meta, 'Legal Guardian' as subtitle 
            FROM users u WHERE role = 'parent'";
} else {
    $sql = "SELECT id, name, email, role as display_role, 'System' as meta, role as subtitle FROM users";
}

if ($search) {
    $sql .= (strpos($sql, 'WHERE') !== false ? " AND " : " WHERE ") . "u.name LIKE " . $pdo->quote("%$search%");
}

try {
    $users = $pdo->query($sql)->fetchAll();
} catch (Exception $e) {
    $users = []; // Handle schema mismatches gracefully
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css?v=2">
</head>
<body>
    <div class="wrapper">
        <?php include('../includes/sidebar.php'); ?>
        <div class="main-content">
            <?php include('../includes/topbar.php'); ?>
            <div class="content-area">
                <div class="d-flex justify-content-between align-items-center mb-5 animate-up">
                    <div>
                        <h2 class="fw-800 mb-1">Global User Directory</h2>
                        <p class="text-muted small fw-600">Overview of all system stakeholders</p>
                    </div>
                    <div class="d-flex gap-3">
                        <select onchange="window.location.href='?role='+this.value" class="form-select border-0 shadow-sm theme-sensitive-bg px-4 py-2 rounded-4 fw-800">
                            <option value="student" <?php echo $role_filter == 'student' ? 'selected' : ''; ?>>Students</option>
                            <option value="teacher" <?php echo $role_filter == 'teacher' ? 'selected' : ''; ?>>Faculty</option>
                            <option value="parent" <?php echo $role_filter == 'parent' ? 'selected' : ''; ?>>Parents</option>
                            <option value="all" <?php echo $role_filter == 'all' ? 'selected' : ''; ?>>All Staff</option>
                        </select>
                    </div>
                </div>

                <div class="card-premium animate-up">
                    <div class="table-responsive">
                        <table class="glass-table">
                            <thead>
                                <tr>
                                    <th>Identity</th>
                                    <th>Role</th>
                                    <th>Metric / Department</th>
                                    <th>Current Status</th>
                                    <th class="text-end">Management</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($users)): ?>
                                    <tr><td colspan="5" class="text-center py-5 text-muted small">No users found in this category.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($users as $u): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="position-relative">
                                                <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=<?php echo $u['name']; ?>" 
                                                     width="45" height="45" class="rounded-4 border" style="background:#fff;">
                                                <div class="position-absolute bottom-0 end-0 bg-success rounded-circle border border-white" style="width:10px; height:10px;"></div>
                                            </div>
                                            <div>
                                                <div class="fw-800 text-main mb-0"><?php echo $u['name']; ?></div>
                                                <div class="smallest text-muted fw-bold"><?php echo $u['email']; ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary bg-opacity-10 text-primary fw-900 uppercase ls-1 px-3 py-2 rounded-3" style="font-size: 0.65rem;">
                                            <?php echo $u['display_role']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-800 small"><?php echo $u['meta'] ?: 'General'; ?></div>
                                        <div class="smallest text-muted fw-bold uppercase ls-1"><?php echo $u['subtitle']; ?></div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="spinner-grow spinner-grow-sm text-success" role="status" style="--bs-spinner-width: 0.5rem; --bs-spinner-height: 0.5rem;"></div>
                                            <span class="smallest fw-900 text-success uppercase ls-1">Verified</span>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <div class="dropdown">
                                            <button class="btn btn-light btn-sm rounded-3 border px-3 fw-800" type="button">
                                                <i class="fas fa-ellipsis-v"></i>
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
        </div>
    </div>
</body>
</html>
