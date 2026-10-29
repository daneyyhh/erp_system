<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'teacher') {
    header("Location: ../login.php"); exit();
}
require_once('../config/db.php');

$success_msg = '';
$error_msg   = '';

// Send single reminder
if (isset($_POST['send_reminder'])) {
    $sid  = (int)$_POST['student_user_id'];
    $item = htmlspecialchars($_POST['fee_item']);
    try {
        $pdo->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'Fee Reminder')")
            ->execute([$sid, "📢 Reminder: Your fee for '$item' is pending. Please clear it at the earliest to avoid any academic holds."]);
        $success_msg = "Reminder sent successfully to the student.";
    } catch (Exception $e) {
        $error_msg = "Failed: " . $e->getMessage();
    }
}

// Send all reminders
if (isset($_POST['remind_all'])) {
    try {
        $pending = $pdo->query("SELECT f.fee_name, u.id as uid FROM fees f JOIN students s ON f.student_id=s.id JOIN users u ON s.user_id=u.id WHERE f.status='pending'")->fetchAll();
        foreach ($pending as $p) {
            $pdo->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'Fee Reminder')")
                ->execute([$p['uid'], "📢 Reminder: Your fee '{$p['fee_name']}' is pending. Please clear it immediately."]);
        }
        $success_msg = count($pending) . " reminder(s) sent to all students with pending fees.";
    } catch (Exception $e) {
        $error_msg = "Failed: " . $e->getMessage();
    }
}

// Fetch fee data
try {
    $fee_overview = $pdo->query("
        SELECT f.*, u.name AS student_name, s.roll_no, s.class, u.id AS student_user_id
        FROM fees f
        JOIN students s ON f.student_id = s.id
        JOIN users u ON s.user_id = u.id
        ORDER BY f.status ASC, s.class, u.name ASC
    ")->fetchAll();
} catch (Exception $e) { $fee_overview = []; }

$pending_count = count(array_filter($fee_overview, fn($f) => $f['status'] !== 'paid'));
$paid_count    = count($fee_overview) - $pending_count;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Reminder | ia Academy</title>
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

            <!-- Header -->
            <div class="page-header animate-up">
                <div>
                    <div class="page-title">Fee Compliance Tracker</div>
                    <div class="page-sub">Monitor pending fees and send reminders to students</div>
                </div>
                <?php if ($pending_count > 0): ?>
                <form method="POST">
                    <button type="submit" name="remind_all" class="btn btn-primary"
                            onclick="return confirm('Send SMS/notification reminders to all <?php echo $pending_count; ?> students with pending fees?')">
                        <i class="fas fa-bell"></i> Remind All (<?php echo $pending_count; ?> pending)
                    </button>
                </form>
                <?php endif; ?>
            </div>

            <!-- Alerts -->
            <?php if ($success_msg): ?>
            <div class="alert alert-success animate-up"><i class="fas fa-check-circle"></i> <?php echo $success_msg; ?></div>
            <?php endif; ?>
            <?php if ($error_msg): ?>
            <div class="alert alert-danger animate-up"><i class="fas fa-exclamation-circle"></i> <?php echo $error_msg; ?></div>
            <?php endif; ?>

            <!-- KPI Row -->
            <div class="row g-3 mb-4 animate-up">
                <div class="col-md-4">
                    <div class="kpi-card">
                        <div class="kpi-icon purple"><i class="fas fa-users"></i></div>
                        <div>
                            <div class="kpi-title">Total Records</div>
                            <div class="kpi-value"><?php echo count($fee_overview); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="kpi-card">
                        <div class="kpi-icon green"><i class="fas fa-check-circle"></i></div>
                        <div>
                            <div class="kpi-title">Fees Paid</div>
                            <div class="kpi-value"><?php echo $paid_count; ?></div>
                            <div class="kpi-badge trend-up">Cleared</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="kpi-card">
                        <div class="kpi-icon red"><i class="fas fa-exclamation-triangle"></i></div>
                        <div>
                            <div class="kpi-title">Fees Pending</div>
                            <div class="kpi-value"><?php echo $pending_count; ?></div>
                            <div class="kpi-badge trend-down">Needs attention</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fee Table -->
            <div class="card animate-up">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="card-title">Class-wise Fee Registry</div>
                    <input type="text" id="feeSearch" class="form-control" style="max-width:240px;"
                           placeholder="Search student..." oninput="filterTable(this.value)">
                </div>
                <div class="table-responsive">
                    <table class="table" id="feeTable">
                        <thead>
                            <tr>
                                <th>Roll No</th>
                                <th>Student</th>
                                <th>Class</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($fee_overview)): ?>
                            <tr><td colspan="6" style="text-align:center;padding:32px;color:var(--text-muted);">No fee records found.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($fee_overview as $fo): ?>
                            <tr>
                                <td style="font-weight:700;color:var(--primary);"><?php echo htmlspecialchars($fo['roll_no'] ?? 'N/A'); ?></td>
                                <td>
                                    <div class="table-user">
                                        <div class="table-avatar"><?php echo strtoupper(substr($fo['student_name'],0,1)); ?></div>
                                        <span style="font-weight:600;"><?php echo htmlspecialchars($fo['student_name']); ?></span>
                                    </div>
                                </td>
                                <td><span class="badge badge-purple"><?php echo htmlspecialchars($fo['class'] ?? '-'); ?></span></td>
                                <td style="font-weight:700;">₹<?php echo number_format($fo['amount'], 2); ?></td>
                                <td>
                                    <?php if ($fo['status'] === 'paid'): ?>
                                        <span class="badge badge-green"><i class="fas fa-check"></i> Paid</span>
                                    <?php else: ?>
                                        <span class="badge badge-red"><i class="fas fa-clock"></i> Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if ($fo['status'] !== 'paid'): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="student_user_id" value="<?php echo $fo['student_user_id']; ?>">
                                        <input type="hidden" name="fee_item" value="<?php echo htmlspecialchars($fo['fee_name'] ?? 'Semester Fee'); ?>">
                                        <button type="submit" name="send_reminder" class="btn btn-sm btn-primary"
                                                onclick="return confirm('Send reminder to <?php echo htmlspecialchars($fo['student_name']); ?>?')">
                                            <i class="fas fa-bell"></i> Remind
                                        </button>
                                    </form>
                                    <?php else: ?>
                                        <span style="font-size:12px;color:var(--text-muted);font-weight:600;">All clear</span>
                                    <?php endif; ?>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function filterTable(q) {
    q = q.toLowerCase();
    document.querySelectorAll('#feeTable tbody tr').forEach(function(row) {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}
</script>
</body>
</html>
