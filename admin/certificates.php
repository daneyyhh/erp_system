<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
require_once('../config/db.php');
require_once('../utils/logger.php');

// Handle Final Approvals
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    if ($_GET['action'] == 'approve') {
        $stmt = $pdo->prepare("UPDATE certificates SET status = 'approved' WHERE id = ?");
        $stmt->execute([$id]);
        logToExcel('Admin Approval', $_SESSION['user_id'], $_SESSION['user_name'], "Final approved certificate ID: $id");
        $msg = "approved";
    } elseif ($_GET['action'] == 'reject') {
        $stmt = $pdo->prepare("UPDATE certificates SET status = 'rejected' WHERE id = ?");
        $stmt->execute([$id]);
        logToExcel('Admin Rejection', $_SESSION['user_id'], $_SESSION['user_name'], "Admin rejected certificate ID: $id");
        $msg = "rejected";
    }
    header("Location: certificates.php?msg=$msg");
    exit();
}

// Fetch requests with statuses 'teacher_approved' or 'approved'
$certs = $pdo->query("SELECT c.*, u.name as student_name, s.roll_no, s.class 
                      FROM certificates c 
                      JOIN students s ON c.student_id = s.id 
                      JOIN users u ON s.user_id = u.id 
                      WHERE c.status IN ('teacher_approved', 'approved') 
                      ORDER BY c.status ASC, c.applied_date DESC")->fetchAll();

$page_title = "Certificate Bureau | Scholarly";
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
<body style="background: #f8fafc;">
    <div class="wrapper">
        <?php include('../includes/sidebar.php'); ?>
        <div class="main-content">
            <?php include('../includes/topbar.php'); ?>
            <div class="content-area">
                <div class="d-flex justify-content-between align-items-center mb-5">
                    <div>
                        <h2 class="fw-800 mb-1">Final Approval Registry</h2>
                        <p class="text-muted small">Issue official documents verified by departmental faculty</p>
                    </div>
                </div>

                <?php if(isset($_GET['msg'])): ?>
                    <div class="alert alert-success border-0 shadow-sm mb-4" style="border-radius:15px;">
                        <i class="fas fa-check-circle me-2"></i> Document status significantly updated to <b><?php echo $_GET['msg']; ?></b>.
                    </div>
                <?php endif; ?>

                <div class="card border-0 shadow-sm" style="border-radius: 30px;">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr class="text-muted small">
                                    <th>STUDENT</th><th>OFFICIAL STATUS</th><th>DOCUMENT TYPE</th><th class="text-end">FINAL DESK ACTION</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($certs)): ?>
                                    <tr><td colspan="4" class="text-center py-5 text-muted small">No verified requests awaiting final approval.</td></tr>
                                <?php endif; ?>
                                <?php foreach($certs as $c): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?php echo $c['student_name']; ?></div>
                                        <code class="smallest text-primary"><?php echo $c['roll_no']; ?></code>
                                    </td>
                                    <td>
                                        <?php if($c['status'] == 'teacher_approved'): ?>
                                            <span class="badge bg-info bg-opacity-10 text-info fw-bold px-3 py-1">Teacher Verified</span>
                                        <?php elseif($c['status'] == 'approved'): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success fw-bold px-3 py-1">Ready for Issuance</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fw-bold"><?php echo $c['type']; ?></td>
                                    <td class="text-end">
                                        <?php if($c['status'] == 'teacher_approved'): ?>
                                            <a href="?action=approve&id=<?php echo $c['id']; ?>" class="btn btn-sm btn-primary px-4 fw-bold shadow-sm" style="border-radius:12px;">Final Approve & Issue</a>
                                            <a href="?action=reject&id=<?php echo $c['id']; ?>" class="btn btn-sm btn-link text-danger text-decoration-none ms-2">Reject</a>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-outline-secondary px-4 fw-bold" style="border-radius:12px;" disabled><i class="fas fa-check-double me-2"></i>Issued</button>
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
</body>
</html>
