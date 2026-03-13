<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'teacher') {
    header("Location: ../login.php");
    exit();
}
require_once('../config/db.php');

// Handle Approval
if (isset($_POST['approve_cert'])) {
    $cert_id = $_POST['cert_id'];
    $stmt = $pdo->prepare("UPDATE certificates SET status = 'approved' WHERE id = ?");
    $stmt->execute([$cert_id]);
    
    // Notify student
    $c_stmt = $pdo->prepare("SELECT s.user_id, c.type FROM certificates c JOIN students s ON c.student_id = s.id WHERE c.id = ?");
    $c_stmt->execute([$cert_id]);
    $c = $c_stmt->fetch();
    
    $msg = "Your request for " . $c['type'] . " Certificate has been approved! You can now download it.";
    $n_stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'Doc Approval')");
    $n_stmt->execute([$c['user_id'], $msg]);
    
    $success_msg = "Certificate approved and student notified!";
}

$page_title = "Document Approvals | Enlight";

// Fetch Pending Certificates
$pending_certs = $pdo->query("SELECT c.*, u.name as student_name, s.roll_no, s.class 
                              FROM certificates c 
                              JOIN students s ON c.student_id = s.id 
                              JOIN users u ON s.user_id = u.id 
                              WHERE c.status = 'pending' 
                              ORDER BY c.applied_date ASC")->fetchAll();
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
                        <h2 class="fw-800 mb-1">Faculty Approvals</h2>
                        <p class="text-muted small fw-600">Review and authorize document requests from your students</p>
                    </div>
                </div>

                <?php if(isset($success_msg)): ?>
                    <div class="alert alert-success border-0 rounded-4 mb-4 shadow-sm animate-up">
                        <i class="fas fa-check-circle me-2"></i> <?php echo $success_msg; ?>
                    </div>
                <?php endif; ?>

                <div class="card-premium animate-up">
                    <h5 class="fw-800 mb-4 px-2">Awaiting Verification</h5>
                    <div class="table-responsive">
                        <table class="glass-table">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Document</th>
                                    <th>Applied On</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($pending_certs)): ?>
                                    <tr><td colspan="4" class="text-center py-5 text-muted small">No pending certificate requests at this moment.</td></tr>
                                <?php endif; ?>
                                <?php foreach($pending_certs as $pc): ?>
                                <tr>
                                    <td class="py-3">
                                        <div class="fw-800 text-main"><?php echo $pc['student_name']; ?></div>
                                        <div class="smallest text-muted fw-bold"><?php echo $pc['roll_no']; ?> • <?php echo $pc['class']; ?></div>
                                    </td>
                                    <td class="fw-800"><?php echo $pc['type']; ?> Cert</td>
                                    <td class="text-muted fw-bold"><?php echo $pc['applied_date']; ?></td>
                                    <td class="text-end">
                                        <form method="POST">
                                            <input type="hidden" name="cert_id" value="<?php echo $pc['id']; ?>">
                                            <button type="submit" name="approve_cert" class="btn btn-premium btn-sm px-4">
                                                <i class="fas fa-check me-2"></i> Approve
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
