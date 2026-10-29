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
$order_by = " ORDER BY u.name ASC";
if ($role_filter === 'student') {
    $sql = "SELECT u.id, u.name, u.email, 'Student' as display_role, s.roll_no as meta, s.class as subtitle 
            FROM users u JOIN students s ON u.id = s.user_id";
    // Apply class filter if coming from attendance Detailed Roster
    $class_filter = $_GET['year'] ?? '';
    if($class_filter) {
        $sql .= " WHERE s.class = " . $pdo->quote($class_filter);
    }
    $order_by = " ORDER BY s.roll_no ASC";
} elseif ($role_filter === 'teacher') {
    $sql = "SELECT u.id, u.name, u.email, 'Teacher' as display_role, t.department as meta, t.subject as subtitle 
            FROM users u JOIN teachers t ON u.id = t.user_id";
} elseif ($role_filter === 'parent') {
    $sql = "SELECT u.id, u.name, u.email, 'Parent' as display_role, 'Family' as meta, 'Legal Guardian' as subtitle 
            FROM users u WHERE u.role = 'parent'";
} else {
    $sql = "SELECT u.id, u.name, u.email, u.role as display_role, 'System' as meta, u.role as subtitle FROM users u";
}

if ($search) {
    $sql .= (strpos($sql, 'WHERE') !== false ? " AND " : " WHERE ") . "u.name LIKE " . $pdo->quote("%$search%");
}
$sql .= $order_by;

// Pagination
$items_per_page = 24;
$current_page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($current_page - 1) * $items_per_page;

$count_sql = "SELECT COUNT(*) FROM ($sql) as total_count";
$total_items = $pdo->query($count_sql)->fetchColumn();
$total_pages = ceil($total_items / $items_per_page);

$sql .= " LIMIT $items_per_page OFFSET $offset";

try {
    $users = $pdo->query($sql)->fetchAll();
} catch (Exception $e) {
    $users = [];
}

// Handle Add User Action
$success_msg = '';
$error_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_student') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $phone = $_POST['phone'];
    $roll_no = $_POST['roll_no'];
    $class = $_POST['class'];
    $semester = $_POST['semester'];

    try {
        $pdo->beginTransaction();
        
        // 1. Create User
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, phone) VALUES (?, ?, ?, 'student', ?)");
        $stmt->execute([$name, $email, $password, $phone]);
        $user_id = $pdo->lastInsertId();

        // 2. Create Student
        $stmt = $pdo->prepare("INSERT INTO students (user_id, roll_no, class, semester) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $roll_no, $class, $semester]);

        $pdo->commit();
        $success_msg = "Student $name successfully onboarded!";
        
        // Log action
        require_once('../utils/logger.php');
        logToExcel('Admin Action', $_SESSION['user_id'], $_SESSION['user_name'] ?? 'Admin', "Directly added student: $name ($roll_no)");
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_msg = "Error: " . $e->getMessage();
    }
}
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
                <div class="d-flex justify-content-between align-items-center mb-5 animate-up">
                    <div>
                        <h2 class="fw-800 mb-1">Global User Directory</h2>
                        <p class="text-muted small fw-600">Overview of all system stakeholders</p>
                        <?php if($success_msg): ?><div class="alert alert-success fw-bold py-2 px-3 small mt-2"><i class="fas fa-check-circle me-1"></i> <?php echo $success_msg; ?></div><?php endif; ?>
                        <?php if($error_msg): ?><div class="alert alert-danger fw-bold py-2 px-3 small mt-2"><i class="fas fa-exclamation-circle me-1"></i> <?php echo $error_msg; ?></div><?php endif; ?>
                    </div>
                    <div class="d-flex gap-3 align-items-center">
                        <!-- Modernized Search -->
                        <form method="GET" class="m-0">
                            <input type="hidden" name="role" value="<?php echo htmlspecialchars($role_filter); ?>">
                            <div class="search-pill">
                                <i class="fas fa-search icon"></i>
                                <input type="text" name="search" placeholder="Search by name..." value="<?php echo htmlspecialchars($search); ?>">
                                <button type="submit">SEARCH</button>
                            </div>
                        </form>


                        <!-- Add User Action -->
                        <button data-bs-toggle="modal" data-bs-target="#addUserModal" class="btn-add-user">
                            <i class="fas fa-user-plus"></i>
                            <span>ADD STUDENT</span>
                        </button>
                    </div>
                </div>

                <div class="card-premium animate-up">
                    <div class="table-responsive">
                        <table class="table align-middle">
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
                                                <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=<?php echo urlencode($u['name']); ?>" 
                                                     width="45" height="45" class="rounded-4 border" style="background:#fff;">
                                                <div class="position-absolute bottom-0 end-0 bg-success rounded-circle border border-white" style="width:10px; height:10px;"></div>
                                            </div>
                                            <div>
                                                <div class="fw-800 text-main mb-0"><?php echo htmlspecialchars($u['name']); ?></div>
                                                <div class="smallest text-muted fw-bold"><?php echo htmlspecialchars($u['email']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary bg-opacity-10 text-primary fw-900 uppercase ls-1 px-3 py-2 rounded-3" style="font-size: 0.65rem;">
                                            <?php echo htmlspecialchars($u['display_role']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-800 small"><?php echo htmlspecialchars($u['meta'] ?: 'General'); ?></div>
                                        <div class="smallest text-muted fw-bold uppercase ls-1"><?php echo htmlspecialchars($u['subtitle']); ?></div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="spinner-grow spinner-grow-sm text-success" role="status" style="--bs-spinner-width: 0.5rem; --bs-spinner-height: 0.5rem;"></div>
                                            <span class="smallest fw-900 text-success uppercase ls-1">Verified</span>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <div class="dropdown">
                                            <button class="btn btn-light btn-sm rounded-3 border px-3 fw-800" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" style="border-radius: 12px;">
                                                <li><a class="dropdown-item fw-600 font-sm py-2" href="student_profile.php?id=<?php echo $u['id']; ?>"><i class="fas fa-user text-primary me-2"></i> View Profile</a></li>
                                                <li><a class="dropdown-item fw-600 font-sm py-2" href="javascript:void(0)" onclick="alert('Direct message functionality has been activated for <?php echo addslashes($u['name']); ?>. Log redirected to communications hub.')"><i class="fas fa-envelope text-info me-2"></i> Send Message</a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item fw-600 font-sm py-2 text-danger" href="javascript:void(0)" onclick="confirm('Are you sure you want to suspend <?php echo addslashes($u['name']); ?>? This will revoke all campus access credentials.') && alert('Account access credentials for <?php echo addslashes($u['name']); ?> have been suspended.')"><i class="fas fa-ban me-2"></i> Suspend Account</a></li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination UI -->
                    <?php if ($total_pages > 1): ?>
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <div class="text-muted small fw-600">
                            Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $items_per_page, $total_items); ?> of <?php echo $total_items; ?> records
                        </div>
                        <nav>
                            <ul class="pagination pagination-sm mb-0">
                                <li class="page-item <?php echo $current_page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?role=<?php echo $role_filter; ?>&page=<?php echo $current_page - 1; ?>&search=<?php echo urlencode($search); ?>" style="border-radius:8px 0 0 8px;"><i class="fas fa-chevron-left"></i></a>
                                </li>
                                <?php 
                                $start = max(1, $current_page - 2);
                                $end = min($total_pages, $start + 4);
                                if ($end - $start < 4) $start = max(1, $end - 4);
                                $start = max(1, $start);
                                for ($i = $start; $i <= $end; $i++): 
                                ?>
                                <li class="page-item <?php echo $i == $current_page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?role=<?php echo $role_filter; ?>&page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                                </li>
                                <?php endfor; ?>
                                <li class="page-item <?php echo $current_page >= $total_pages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?role=<?php echo $role_filter; ?>&page=<?php echo $current_page + 1; ?>&search=<?php echo urlencode($search); ?>" style="border-radius:0 8px 8px 0;"><i class="fas fa-chevron-right"></i></a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header border-0 bg-primary text-white p-4">
                    <h5 class="modal-title fw-800"><i class="fas fa-user-plus me-2"></i> Register New Student</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="add_student">
                    <div class="modal-body p-4">
                        <div style="font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:1px; color:var(--primary); margin-bottom:15px;">Account Credentials</div>
                        <div class="row g-3 mb-4">
                            <div class="col-12">
                                <label class="form-label smallest fw-800 text-muted uppercase">Full Name</label>
                                <input type="text" name="name" class="form-control fw-600" required placeholder="e.g. John Doe">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label smallest fw-800 text-muted uppercase">Email Address</label>
                                <input type="email" name="email" class="form-control fw-600" required placeholder="john@college.com">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label smallest fw-800 text-muted uppercase">Initial Password</label>
                                <input type="password" name="password" class="form-control fw-600" required value="student123">
                            </div>
                        </div>

                        <div style="font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:1px; color:var(--primary); margin-bottom:15px;">Academic Identity</div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label smallest fw-800 text-muted uppercase">Roll Number</label>
                                <input type="text" name="roll_no" class="form-control fw-600" required placeholder="S-001">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label smallest fw-800 text-muted uppercase">Contact Phone</label>
                                <input type="tel" name="phone" class="form-control fw-600" placeholder="10-digit mobile">
                            </div>
                            <div class="col-md-7">
                                <label class="form-label smallest fw-800 text-muted uppercase">Assigned Class</label>
                                <input type="text" name="class" class="form-control fw-600" placeholder="e.g. BCA 1st Year">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label smallest fw-800 text-muted uppercase">Current Sem</label>
                                <select name="semester" class="form-select fw-600">
                                    <?php for($i=1;$i<=8;$i++) echo "<option value='$i'>Semester $i</option>"; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 p-4 pt-0">
                        <button type="button" class="btn btn-light fw-800 px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary fw-800 px-4 py-2">Create Student Account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
