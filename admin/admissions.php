<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
require_once('../config/db.php');

$page_title = "Admission Management";

// Extend the applications table if needed
$pdo->exec("CREATE TABLE IF NOT EXISTS applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255),
    email VARCHAR(255),
    phone VARCHAR(30),
    dob DATE,
    gender VARCHAR(20),
    fathers_name VARCHAR(255),
    mothers_name VARCHAR(255),
    address TEXT,
    course VARCHAR(100),
    mark_10th DECIMAL(5,2),
    mark_12th DECIMAL(5,2),
    school_name VARCHAR(255),
    date DATE,
    status VARCHAR(50) DEFAULT 'Applied'
)");

// Add missing columns safely (ignore errors if already exist)
$columns_to_add = [
    'email'        => "ALTER TABLE applications ADD COLUMN email VARCHAR(255) AFTER name",
    'phone'        => "ALTER TABLE applications ADD COLUMN phone VARCHAR(30) AFTER email",
    'dob'          => "ALTER TABLE applications ADD COLUMN dob DATE AFTER phone",
    'gender'       => "ALTER TABLE applications ADD COLUMN gender VARCHAR(20) AFTER dob",
    'fathers_name' => "ALTER TABLE applications ADD COLUMN fathers_name VARCHAR(255) AFTER gender",
    'mothers_name' => "ALTER TABLE applications ADD COLUMN mothers_name VARCHAR(255) AFTER fathers_name",
    'address'      => "ALTER TABLE applications ADD COLUMN address TEXT AFTER mothers_name",
    'mark_10th'    => "ALTER TABLE applications ADD COLUMN mark_10th DECIMAL(5,2) AFTER course",
    'mark_12th'    => "ALTER TABLE applications ADD COLUMN mark_12th DECIMAL(5,2) AFTER mark_10th",
    'school_name'  => "ALTER TABLE applications ADD COLUMN school_name VARCHAR(255) AFTER mark_12th",
];
foreach ($columns_to_add as $col => $sql) {
    try { $pdo->exec($sql); } catch (PDOException $e) { /* column exists, skip */ }
}

// Handle new application
if (isset($_POST['add_application'])) {
    $stmt = $pdo->prepare("INSERT INTO applications 
        (name, email, phone, dob, gender, fathers_name, mothers_name, address, course, mark_10th, mark_12th, school_name, date, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), 'Applied')");
    $stmt->execute([
        $_POST['student_name'],
        $_POST['email'],
        $_POST['phone'],
        $_POST['dob'],
        $_POST['gender'],
        $_POST['fathers_name'],
        $_POST['mothers_name'],
        $_POST['address'],
        $_POST['course'],
        $_POST['mark_10th'],
        $_POST['mark_12th'],
        $_POST['school_name'],
    ]);
    header("Location: admissions.php?msg=added");
    exit();
}

// Handle approve/reject
if (isset($_GET['action']) && isset($_GET['id'])) {
    $status = $_GET['action'] == 'approve' ? 'Admitted' : 'Rejected';
    $pdo->prepare("UPDATE applications SET status = ? WHERE id = ?")->execute([$status, $_GET['id']]);
    require_once('../utils/logger.php');
    logToExcel('Admin Approval', $_SESSION['user_id'], $_SESSION['user_name'], "Application ID {$_GET['id']} $status");
    header("Location: admissions.php?msg=success");
    exit();
}

// Handle delete
if (isset($_GET['delete_id'])) {
    $pdo->prepare("DELETE FROM applications WHERE id = ?")->execute([$_GET['delete_id']]);
    header("Location: admissions.php?msg=deleted");
    exit();
}

$applications = $pdo->query("SELECT * FROM applications ORDER BY date DESC")->fetchAll();

// Stats
$total    = count($applications);
$admitted = count(array_filter($applications, fn($a) => $a['status'] === 'Admitted'));
$pending  = count(array_filter($applications, fn($a) => $a['status'] === 'Applied' || $a['status'] === 'Under Review'));
$rejected = count(array_filter($applications, fn($a) => $a['status'] === 'Rejected'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $page_title; ?> | ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <style>
        .stat-tile {
            background: #fff;
            border: 1.5px solid #EDE9FE;
            border-radius: 14px;
            padding: 18px 22px;
            display: flex; align-items: center; gap: 14px;
        }
        .stat-icon {
            width: 42px; height: 42px; border-radius: 11px;
            display: flex; align-items: center; justify-content: center;
            font-size: 17px; flex-shrink: 0;
        }
        .stat-num { font-size: 22px; font-weight: 800; color: #1E1B4B; line-height: 1; }
        .stat-label { font-size: 12px; color: #6B7280; margin-top: 2px; }
    </style>
</head>
<body>
<div class="wrapper">
    <?php include('../includes/sidebar.php'); ?>
    <div class="main-content">
        <?php include('../includes/topbar.php'); ?>
        <div class="content-area">

            <?php if(isset($_GET['msg'])): ?>
                <div class="alert alert-success animate-up mb-4">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $_GET['msg'] === 'added' ? 'Application submitted successfully!' : ($_GET['msg'] === 'deleted' ? 'Application deleted.' : 'Status updated.'); ?>
                </div>
            <?php endif; ?>

            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-800 mb-1">Admissions</h2>
                    <p class="text-muted" style="margin:0;font-size:13px;">Track and manage student enrollment applications</p>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newAppModal">
                    <i class="fas fa-plus"></i> New Application
                </button>
            </div>

            <!-- Stats -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="stat-tile">
                        <div class="stat-icon" style="background:#F5F3FF;color:#7C3AED;"><i class="fas fa-file-alt"></i></div>
                        <div><div class="stat-num"><?php echo $total; ?></div><div class="stat-label">Total Applications</div></div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-tile">
                        <div class="stat-icon" style="background:#DCFCE7;color:#16A34A;"><i class="fas fa-user-graduate"></i></div>
                        <div><div class="stat-num"><?php echo $admitted; ?></div><div class="stat-label">Admitted</div></div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-tile">
                        <div class="stat-icon" style="background:#FFFBEB;color:#D97706;"><i class="fas fa-hourglass-half"></i></div>
                        <div><div class="stat-num"><?php echo $pending; ?></div><div class="stat-label">Pending Review</div></div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-tile">
                        <div class="stat-icon" style="background:#FEF2F2;color:#DC2626;"><i class="fas fa-times-circle"></i></div>
                        <div><div class="stat-num"><?php echo $rejected; ?></div><div class="stat-label">Rejected</div></div>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>App ID</th>
                                <th>Student Name</th>
                                <th>Course</th>
                                <th>Contact</th>
                                <th>10th %</th>
                                <th>12th %</th>
                                <th>Applied Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($applications)): ?>
                                <tr><td colspan="9" class="text-center py-5 text-muted">No applications submitted yet.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($applications as $app):
                                $sc = match($app['status']) {
                                    'Applied'      => ['bg'=>'#DBEAFE','color'=>'#1D4ED8'],
                                    'Under Review' => ['bg'=>'#FFFBEB','color'=>'#D97706'],
                                    'Admitted'     => ['bg'=>'#DCFCE7','color'=>'#16A34A'],
                                    'Rejected'     => ['bg'=>'#FEF2F2','color'=>'#DC2626'],
                                    default        => ['bg'=>'#F3F4F6','color'=>'#6B7280'],
                                };
                            ?>
                            <tr>
                                <td style="font-size:12px;font-weight:700;color:#9CA3AF;">#APP-<?php echo str_pad($app['id'],4,'0',STR_PAD_LEFT); ?></td>
                                <td>
                                    <div style="font-weight:700;color:#1E1B4B;"><?php echo htmlspecialchars($app['name']); ?></div>
                                    <div style="font-size:12px;color:#9CA3AF;"><?php echo htmlspecialchars($app['email'] ?? ''); ?></div>
                                </td>
                                <td><span style="background:#F5F3FF;color:#7C3AED;padding:4px 10px;border-radius:8px;font-size:12px;font-weight:700;"><?php echo htmlspecialchars($app['course']); ?></span></td>
                                <td style="font-size:13px;color:#374151;"><?php echo htmlspecialchars($app['phone'] ?? '-'); ?></td>
                                <td style="font-weight:600;"><?php echo $app['mark_10th'] ? $app['mark_10th'].'%' : '-'; ?></td>
                                <td style="font-weight:600;"><?php echo $app['mark_12th'] ? $app['mark_12th'].'%' : '-'; ?></td>
                                <td style="font-size:13px;color:#6B7280;"><?php echo $app['date']; ?></td>
                                <td>
                                    <span style="background:<?php echo $sc['bg']; ?>;color:<?php echo $sc['color']; ?>;padding:4px 12px;border-radius:9999px;font-size:12px;font-weight:700;">
                                        <?php echo $app['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <!-- View modal trigger -->
                                    <button class="btn btn-sm" style="background:#F5F3FF;color:#7C3AED;border-radius:8px;padding:6px 10px;"
                                        onclick="viewApp(<?php echo htmlspecialchars(json_encode($app)); ?>)"
                                        title="View Details"><i class="fas fa-eye"></i>
                                    </button>
                                    <?php if($app['status'] !== 'Admitted' && $app['status'] !== 'Rejected'): ?>
                                    <a href="?action=approve&id=<?php echo $app['id']; ?>"
                                       class="btn btn-sm ms-1" style="background:#DCFCE7;color:#16A34A;border-radius:8px;padding:6px 10px;"
                                       title="Admit"><i class="fas fa-check"></i></a>
                                    <a href="?action=reject&id=<?php echo $app['id']; ?>"
                                       class="btn btn-sm ms-1" style="background:#FEF2F2;color:#DC2626;border-radius:8px;padding:6px 10px;"
                                       title="Reject"><i class="fas fa-times"></i></a>
                                    <?php endif; ?>
                                    <a href="?delete_id=<?php echo $app['id']; ?>"
                                       class="btn btn-sm ms-1" style="background:#F3F4F6;color:#6B7280;border-radius:8px;padding:6px 10px;"
                                       onclick="return confirm('Delete this application?')"
                                       title="Delete"><i class="fas fa-trash-alt"></i></a>
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

<!-- New Application Modal (detailed form) -->
<div class="modal fade" id="newAppModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">New Admission Application</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <!-- Section: Personal Info -->
                    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#7C3AED;margin-bottom:12px;">
                        <i class="fas fa-user me-1"></i> Personal Information
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Full Name *</label>
                            <input type="text" name="student_name" class="form-control" required placeholder="e.g. Rahul Sharma">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date of Birth *</label>
                            <input type="date" name="dob" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Gender *</label>
                            <select name="gender" class="form-select" required>
                                <option value="">Select...</option>
                                <option>Male</option>
                                <option>Female</option>
                                <option>Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email Address *</label>
                            <input type="email" name="email" class="form-control" required placeholder="student@email.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone Number *</label>
                            <input type="tel" name="phone" class="form-control" required placeholder="+91 9876543210">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Address</label>
                            <input type="text" name="address" class="form-control" placeholder="City, State">
                        </div>
                    </div>

                    <hr style="border-color:#EDE9FE;margin:20px 0;">
                    <!-- Section: Family -->
                    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#7C3AED;margin-bottom:12px;">
                        <i class="fas fa-users me-1"></i> Family Details
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Father's Name *</label>
                            <input type="text" name="fathers_name" class="form-control" required placeholder="Father's full name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Mother's Name *</label>
                            <input type="text" name="mothers_name" class="form-control" required placeholder="Mother's full name">
                        </div>
                    </div>

                    <hr style="border-color:#EDE9FE;margin:20px 0;">
                    <!-- Section: Academic -->
                    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#7C3AED;margin-bottom:12px;">
                        <i class="fas fa-graduation-cap me-1"></i> Academic Details
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">10th Mark (%) *</label>
                            <input type="number" step="0.01" min="0" max="100" name="mark_10th" class="form-control" required placeholder="e.g. 85.5">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">12th Mark (%) *</label>
                            <input type="number" step="0.01" min="0" max="100" name="mark_12th" class="form-control" required placeholder="e.g. 78.0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Previous School / College Name</label>
                            <input type="text" name="school_name" class="form-control" placeholder="e.g. St. Joseph's School">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Desired Course *</label>
                            <select name="course" class="form-select" required>
                                <option value="">Select course...</option>
                                <option>BCA – Bachelor of Computer Applications</option>
                                <option>BBA – Bachelor of Business Administration</option>
                                <option>BSc Computer Science</option>
                                <option>BCom – Bachelor of Commerce</option>
                                <option>BSc Mathematics</option>
                                <option>MBA – Master of Business Administration</option>
                                <option>MCA – Master of Computer Applications</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm" style="background:#F5F3FF;color:#7C3AED;border-radius:8px;" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_application" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Submit Application
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Details Modal -->
<div class="modal fade" id="viewAppModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Application Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewAppBody">
                <!-- Populated by JS -->
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function viewApp(app) {
    var body = document.getElementById('viewAppBody');
    var row = function(label, val) {
        return '<div style="display:flex;gap:8px;padding:10px 0;border-bottom:1px solid #F5F3FF;">'
             + '<div style="width:180px;font-size:11.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#A78BFA;flex-shrink:0;">' + label + '</div>'
             + '<div style="font-size:14px;font-weight:500;color:#1E1B4B;">' + (val || '<span style="color:#D1D5DB;">—</span>') + '</div>'
             + '</div>';
    };
    body.innerHTML =
        '<div style="padding:4px 0;">'
      + row('Full Name', app.name)
      + row('Date of Birth', app.dob)
      + row('Gender', app.gender)
      + row('Email', app.email)
      + row('Phone', app.phone)
      + row('Address', app.address)
      + row("Father's Name", app.fathers_name)
      + row("Mother's Name", app.mothers_name)
      + row('10th Marks', app.mark_10th ? app.mark_10th + '%' : null)
      + row('12th Marks', app.mark_12th ? app.mark_12th + '%' : null)
      + row('School / College', app.school_name)
      + row('Applied Course', app.course)
      + row('Applied On', app.date)
      + row('Current Status', '<span style="background:#F5F3FF;color:#7C3AED;padding:3px 10px;border-radius:9999px;font-weight:700;font-size:12px;">' + app.status + '</span>')
      + '</div>';
    new bootstrap.Modal(document.getElementById('viewAppModal')).show();
}
</script>
</body>
</html>
