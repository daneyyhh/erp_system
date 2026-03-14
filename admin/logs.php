<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
require_once('../config/db.php');

$page_title = "Action Ledger | Project ERP";

$logs = $pdo->query("SELECT * FROM logs ORDER BY timestamp DESC LIMIT 100")->fetchAll();

function getActionBadge($action) {
    if (stripos($action, 'login') !== false) return 'cat-user-login';
    if (stripos($action, 'admin approval') !== false) return 'cat-admin-approval';
    if (stripos($action, 'teacher verification') !== false) return 'cat-teacher-verification';
    if (stripos($action, 'cert request') !== false) return 'cat-cert-request';
    if (stripos($action, 'attendance') !== false) return 'cat-attendance';
    return 'bg-secondary text-white';
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
                        <h2 class="fw-800 mb-1">System Audit Ledger</h2>
                        <p class="text-muted small fw-600">Real-time action tracking for compliance and registry</p>
                    </div>
                    <div class="d-flex gap-3">
                        <button class="btn btn-light border fw-bold px-4" onclick="location.reload()" style="border-radius:12px;">
                            <i class="fas fa-rotate me-2 text-primary"></i> Sync DB
                        </button>
                        <a href="export_logs.php" class="btn btn-premium shadow-lg">
                            <i class="fas fa-cloud-download-alt me-2"></i> Export Excel
                        </a>
                    </div>
                </div>

                <div class="card-premium animate-up" style="--delay: 0.2s">
                    <div class="table-responsive">
                        <table class="glass-table">
                            <thead>
                                <tr>
                                    <th>TIMESTAMP</th>
                                    <th>ACTION CATEGORY</th>
                                    <th>PERFORMED BY</th>
                                    <th>DETAILED REMARKS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($logs)): ?>
                                    <tr><td colspan="4" class="text-center py-5 text-muted">No activity logs recorded yet.</td></tr>
                                <?php endif; ?>
                                <?php foreach($logs as $log): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold d-flex align-items-center">
                                            <i class="far fa-clock me-2 text-muted small"></i>
                                            <?php echo $log['timestamp']; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge-category <?php echo getActionBadge($log['action']); ?>">
                                            <?php echo $log['action']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="bg-light rounded-circle p-1 d-flex align-items-center justify-content-center" style="width:30px; height:30px;">
                                                <i class="fas fa-user-shield text-muted small"></i>
                                            </div>
                                            <div>
                                                <div class="fw-800 small"><?php echo $log['user_name']; ?></div>
                                                <div class="smallest text-muted fw-bold">ID: #<?php echo $log['user_id']; ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-muted small fw-600"><?php echo $log['details']; ?></td>
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
