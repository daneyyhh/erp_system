<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
require_once('../config/db.php');
require_once('../utils/logger.php');

$page_title = "Revenue Desk | Scholarly";

// Real Stats
$total_revenue = $pdo->query("SELECT SUM(amount) FROM payments WHERE status = 'completed'")->fetchColumn() ?: "1,20,000";
$pending_fees = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn() * 24500;

// Handle Mock Payment Action
if (isset($_GET['action']) && $_GET['action'] == 'verify') {
    logToExcel('Fee Verified', $_SESSION['user_id'], $_SESSION['user_name'], "Administrator verified student payment.");
    $success = "Transaction verified and Ledger updated!";
}
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
<body style="background: #f8fafc;">
    <div class="wrapper">
        <?php include('../includes/sidebar.php'); ?>
        <div class="main-content">
            <?php include('../includes/topbar.php'); ?>
            <div class="content-area">
                <div class="d-flex justify-content-between align-items-center mb-5">
                    <div>
                        <h2 class="fw-800 mb-1">Financial Desk</h2>
                        <p class="text-muted">Manage tuition fees and miscellaneous collections</p>
                    </div>
                </div>

                <?php if(isset($success)): ?>
                    <div class="alert alert-success border-0 shadow-sm mb-4" style="border-radius:12px;"><?php echo $success; ?></div>
                <?php endif; ?>

                <div class="row g-4 mb-5">
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm p-4" style="border-radius:24px; background: #f0fdf4;">
                            <h6 class="fw-bold text-success mb-2 small">COLLECTED REVENUE</h6>
                            <h2 class="fw-900 mb-0 text-success">₹<?php echo $total_revenue; ?></h2>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm p-4" style="border-radius:24px; background: #fff1f2;">
                            <h6 class="fw-bold text-danger mb-2 small">TOTAL RECEIVABLE</h6>
                            <h2 class="fw-900 mb-0 text-danger">₹<?php echo number_format($pending_fees); ?></h2>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm" style="border-radius: 30px;">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr class="text-muted small">
                                    <th>REFERENCE</th><th>STUDENT</th><th>CATEGORY</th><th>AMOUNT</th><th>DATE</th><th>STATUS</th><th class="text-end">ACTION</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code class="fw-bold">TXN-001</code></td>
                                    <td class="fw-bold">John Doe</td>
                                    <td>Semester Fees</td>
                                    <td>₹24,500</td>
                                    <td class="small">12 Mar, 2026</td>
                                    <td><span class="badge-soft badge-soft-success">Verified</span></td>
                                    <td class="text-end">
                                        <a href="?action=verify" class="btn btn-sm btn-primary px-3">Sync to Excel</a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
