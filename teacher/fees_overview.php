<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'teacher') {
    header("Location: ../login.php");
    exit();
}
require_once('../config/db.php');

// Handle Reminder Action
if (isset($_POST['send_reminder'])) {
    $student_user_id = $_POST['student_user_id'];
    $fee_item = $_POST['fee_item'];
    
    $msg = "Reminder: Your fee for '$fee_item' is pending. Please clear it at the earliest.";
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'Fee Reminder')");
    $stmt->execute([$student_user_id, $msg]);
    $success_msg = "Reminder sent successfully!";
}

$page_title = "Students Fees Overview | Enlight";

$fee_overview = $pdo->query("SELECT f.*, u.name as student_name, s.roll_no, s.class, u.id as student_user_id 
                             FROM fees f 
                             JOIN students s ON f.student_id = s.id 
                             JOIN users u ON s.user_id = u.id 
                             ORDER BY s.class, u.name ASC")->fetchAll();
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
                        <h2 class="fw-800 mb-1">Fee Compliance Tracker</h2>
                        <p class="text-muted small fw-600">Monitor your students' financial status for academic eligibility</p>
                    </div>
                </div>

                <?php if(isset($success_msg)): ?>
                    <div class="alert alert-success border-0 rounded-4 mb-4 shadow-sm animate-up">
                        <i class="fas fa-check-circle me-2"></i> <?php echo $success_msg; ?>
                    </div>
                <?php endif; ?>

                <div class="card-premium animate-up">
                    <h5 class="fw-800 mb-4 px-2">Class-wise Fee Registry</h5>
                    <div class="table-responsive">
                        <table class="glass-table">
                            <thead>
                                <tr>
                                    <th>Roll No</th>
                                    <th>Student</th>
                                    <th>Batch</th>
                                    <th>Course Fee</th>
                                    <th>Status</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($fee_overview as $fo): ?>
                                <tr>
                                    <td class="fw-bold text-primary"><?php echo $fo['roll_no']; ?></td>
                                    <td class="fw-800"><?php echo $fo['student_name']; ?></td>
                                    <td><span class="badge-category cat-user-login"><?php echo $fo['class']; ?></span></td>
                                    <td class="fw-800">₹<?php echo number_format($fo['amount'], 2); ?></td>
                                    <td>
                                        <?php if($fo['status'] == 'paid'): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success fw-extrabold p-2 small px-3">PAID</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger bg-opacity-10 text-danger fw-extrabold p-2 small px-3">DUE</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if($fo['status'] !== 'paid'): ?>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="student_user_id" value="<?php echo $fo['student_user_id']; ?>">
                                                <input type="hidden" name="fee_item" value="Semester Tuition">
                                                <button type="submit" name="send_reminder" class="btn btn-sm btn-premium py-2 px-3">
                                                    <i class="fas fa-bell me-2"></i> Send Reminder
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted small fw-bold">No Action Needed</span>
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
