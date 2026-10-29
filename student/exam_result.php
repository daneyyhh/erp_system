<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$attempt_id = $_GET['attempt_id'] ?? null;
$score = (int)$_GET['score'];
$total = (int)$_GET['total'];
$percent = (float)$_GET['percent'];

if (!$attempt_id) {
    header("Location: exams.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Exam Result | Enlight</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body style="background: #f8fafc;">
    <div class="wrapper">
        <?php include('../includes/sidebar.php'); ?>
        <div class="main-content">
            <?php include('../includes/topbar.php'); ?>
            <div class="content-area">
                <div class="row justify-content-center">
                    <div class="col-lg-6">
                        <div class="card-premium text-center py-5 animate-up">
                            <div class="mb-4">
                                <?php if($percent >= 40): ?>
                                    <i class="fas fa-trophy fa-5x text-warning"></i>
                                <?php else: ?>
                                    <i class="fas fa-book-reader fa-5x text-primary"></i>
                                <?php endif; ?>
                            </div>
                            
                            <h2 class="fw-900 mb-2">
                                <?php if($percent >= 60): ?>
                                    Excellent Work!
                                <?php elseif($percent >= 40): ?>
                                    Good Job!
                                <?php else: ?>
                                    Keep Trying!
                                <?php endif; ?>
                            </h2>
                            
                            <p class="text-muted mb-4">
                                <?php if($percent >= 60): ?>
                                    You have passed the exam with distinction.
                                <?php elseif($percent >= 40): ?>
                                    You have passed the exam. Keep improving!
                                <?php else: ?>
                                    You need 40% to pass. Review and try again.
                                <?php endif; ?>
                            </p>
                            
                            <div class="bg-<?php echo $percent >= 40 ? 'success' : 'danger'; ?>-subtle rounded-4 p-4 mb-4">
                                <div class="display-1 fw-900 text-<?php echo $percent >= 40 ? 'success' : 'danger'; ?>">
                                    <?php echo round($percent, 1); ?>%
                                </div>
                                <div class="fw-bold text-muted">
                                    Score: <?php echo $score; ?> / <?php echo $total; ?> marks
                                </div>
                            </div>
                            
                            <div class="d-flex gap-3 justify-content-center">
                                <a href="exams.php" class="btn btn-outline-primary rounded-4 fw-bold px-4">
                                    <i class="fas fa-arrow-left me-2"></i> Back to Exams
                                </a>
                                <a href="../download.php?type=exam_result" class="btn btn-premium rounded-4 fw-800 px-4">
                                    <i class="fas fa-download me-2"></i> Download Result
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
