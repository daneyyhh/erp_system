<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
require_once('../config/db.php');

// Handle Status Toggle
if (isset($_POST['toggle_status'])) {
    $fee_id = $_POST['fee_id'];
    $current_status = $_POST['current_status'];
    $new_status = ($current_status === 'paid') ? 'pending' : 'paid';
    
    $stmt = $pdo->prepare("UPDATE fees SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $fee_id]);
    $success_msg = "Fee status updated to " . strtoupper($new_status);
}

$page_title = "Fees Revenue Overall | Project ERP";

// Fetch Overall Stats
$total_revenue = $pdo->query("SELECT SUM(amount) FROM fees WHERE status='paid'")->fetchColumn() ?: 0;
$pending_revenue = $pdo->query("SELECT SUM(amount) FROM fees WHERE status='pending'")->fetchColumn() ?: 0;
$overdue_revenue = $pdo->query("SELECT SUM(amount) FROM fees WHERE status='overdue'")->fetchColumn() ?: 0;

// Fetch All Ledger
$transactions = $pdo->query("SELECT f.*, u.name as student_name, s.roll_no 
                             FROM fees f 
                             JOIN students s ON f.student_id = s.id 
                             JOIN users u ON s.user_id = u.id 
                             ORDER BY f.id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="wrapper">
        <?php include('../includes/sidebar.php'); ?>
        <div class="main-content">
            <?php include('../includes/topbar.php'); ?>
            <div class="content-area">
                <div class="d-flex justify-content-between align-items-center mb-5 animate-up">
                    <div>
                        <h2 class="fw-800 mb-1">Financial Oversight</h2>
                        <p class="text-muted small fw-600">Global revenue tracking and fee collection status</p>
                    </div>
                    <a href="../download.php?type=report&id=revenue" class="btn btn-premium px-4 py-3 rounded-4 fw-800">
                        <i class="fas fa-file-export me-2"></i> Download Revenue Report
                    </a>
                </div>

                <?php if(isset($success_msg)): ?>
                    <div class="alert alert-success border-0 rounded-4 mb-4 shadow-sm animate-up">
                        <i class="fas fa-check-circle me-2"></i> <?php echo $success_msg; ?>
                    </div>
                <?php endif; ?>

                <div class="row g-4 mb-5">
                    <div class="col-md-4">
                        <div class="card-premium animate-up">
                            <div class="kpi-title">TOTAL COLLECTED</div>
                            <div class="kpi-value text-success">₹<?php echo number_format($total_revenue, 2); ?></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card-premium animate-up" style="--delay: 0.1s">
                            <div class="kpi-title">PENDING REVENUE</div>
                            <div class="kpi-value text-warning">₹<?php echo number_format($pending_revenue, 2); ?></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card-premium animate-up" style="--delay: 0.2s">
                            <div class="kpi-title">OVERDUE REVENUE</div>
                            <div class="kpi-value text-danger">₹<?php echo number_format($overdue_revenue, 2); ?></div>
                        </div>
                    </div>
                </div>

                <div class="card-premium animate-up">
                    <h5 class="fw-800 mb-4 px-2">Institutional Fee Ledger</h5>
                    <div class="table-responsive">
                        <table class="glass-table">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Roll No</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th class="text-end">Administrative Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($transactions as $t): ?>
                                <tr>
                                    <td class="fw-800"><?php echo $t['student_name']; ?></td>
                                    <td class="fw-bold text-muted"><?php echo $t['roll_no']; ?></td>
                                    <td class="fw-800 text-primary">₹<?php echo number_format($t['amount'], 2); ?></td>
                                    <td>
                                        <?php if($t['status'] == 'paid'): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success fw-bold p-2 small px-3">PAID</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger bg-opacity-10 text-danger fw-bold p-2 small px-3">UNPAID</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <form method="POST">
                                            <input type="hidden" name="fee_id" value="<?php echo $t['id']; ?>">
                                            <input type="hidden" name="current_status" value="<?php echo $t['status']; ?>">
                                            <button type="submit" name="toggle_status" class="btn btn-sm <?php echo $t['status'] === 'paid' ? 'btn-outline-danger' : 'btn-outline-success'; ?> fw-800 px-3">
                                                <i class="fas <?php echo $t['status'] === 'paid' ? 'fa-times-circle' : 'fa-check-circle'; ?> me-2"></i>
                                                Mark as <?php echo $t['status'] === 'paid' ? 'Unpaid' : 'Paid'; ?>
                                            </button>
                                        </form>
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
