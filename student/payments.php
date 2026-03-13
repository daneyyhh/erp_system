<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}
require_once('../config/db.php');

// Handle Payment Action
if (isset($_POST['pay_now'])) {
    $fee_id = $_POST['fee_id'];
    $stmt = $pdo->prepare("UPDATE fees SET status = 'paid', paid_date = CURDATE() WHERE id = ?");
    $stmt->execute([$fee_id]);
    $success_msg = "Payment successful! Receipt generated and linked to your profile.";
    
    // Log the action for student
    $log_msg = "Your payment for Semester Tuition Fee was successful. Receipt generated.";
    $log_stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'Fee Payment')");
    $log_stmt->execute([$_SESSION['user_id'], $log_msg]);

    // Notify Teachers
    $student_name = $_SESSION['user_name'];
    $teacher_msg = "Student $student_name has successfully paid their Semester Tuition Fees.";
    $teachers = $pdo->query("SELECT user_id FROM users WHERE role = 'teacher'")->fetchAll();
    foreach($teachers as $t) {
        $notif_stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'Fee Update')");
        $notif_stmt->execute([$t['user_id'], $teacher_msg]);
    }
}

$page_title = "Tuition & Fees | Enlight";

// Fetch real fees from DB
$stmt = $pdo->prepare("SELECT f.* FROM fees f JOIN students s ON f.student_id = s.id WHERE s.user_id = ? ORDER BY f.status DESC, f.due_date ASC");
$stmt->execute([$_SESSION['user_id']]);
$fees = $stmt->fetchAll();
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
                        <h2 class="fw-800 mb-1">Financial Overview</h2>
                        <p class="text-muted small fw-600">Manage your tuition fees and payment history</p>
                    </div>
                </div>

                <?php if(isset($success_msg)): ?>
                    <div class="alert alert-success border-0 rounded-4 mb-4 shadow-sm animate-up" style="background: #ecfdf5; color: #065f46;">
                        <i class="fas fa-check-circle me-2"></i> <?php echo $success_msg; ?>
                    </div>
                <?php endif; ?>

                <div class="card-premium animate-up">
                    <h5 class="fw-800 mb-4 px-2">Academic Dues Ledger</h5>
                    <div class="table-responsive">
                        <table class="glass-table">
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <th>Due Date</th>
                                    <th>Amount (INR)</th>
                                    <th>Status</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($fees)): ?>
                                    <tr><td colspan="5" class="text-center py-5 text-muted">No academic fees recorded yet.</td></tr>
                                <?php endif; ?>
                                <?php foreach($fees as $f): ?>
                                <tr>
                                    <td class="fw-800">Semester Tuition Fee</td>
                                    <td class="text-muted fw-bold"><?php echo $f['due_date']; ?></td>
                                    <td class="fw-800 text-primary">₹<?php echo number_format($f['amount'], 2); ?></td>
                                    <td>
                                        <?php if($f['status'] == 'paid'): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success fw-bold p-2 small px-3">PAID</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger bg-opacity-10 text-danger fw-bold p-2 small px-3">PENDING</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if($f['status'] !== 'paid'): ?>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="fee_id" value="<?php echo $f['id']; ?>">
                                                <button type="submit" name="pay_now" class="btn btn-premium btn-sm px-4 py-2">
                                                    <i class="fas fa-credit-card me-2"></i> Pay Now
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <a href="../download.php?type=receipt&id=<?php echo $f['id']; ?>" class="btn btn-light btn-sm px-4 py-2 border fw-800">
                                                <i class="fas fa-file-invoice me-2"></i> Receipt
                                            </a>
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
