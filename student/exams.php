<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}
require_once('../config/db.php');

$page_title = "Online Exams | Enlight";

$stmt = $pdo->prepare("SELECT s.id FROM students s WHERE s.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$student_id = $stmt->fetchColumn();
$student_class = $pdo->prepare("SELECT class FROM students WHERE id = ?");
$student_class->execute([$student_id]);
$student_class = $student_class->fetchColumn();

$now = date('Y-m-d H:i:s');
$exams = $pdo->prepare("SELECT e.*, s.name as subject_name,
    (SELECT COUNT(*) FROM exam_attempts WHERE exam_id = e.id AND student_id = ?) as attempted,
    (SELECT status FROM exam_attempts WHERE exam_id = e.id AND student_id = ? LIMIT 1) as attempt_status
    FROM exams e 
    LEFT JOIN subjects s ON e.subject_id = s.id
    WHERE e.status = 'active' AND e.class = ?
    ORDER BY e.start_time DESC");
$exams->execute([$student_id, $student_id, $student_class]);
$available_exams = $exams->fetchAll();

$past_exams = $pdo->prepare("SELECT e.*, s.name as subject_name, ea.score, ea.end_time, ea.status as attempt_status
    FROM exam_attempts ea
    JOIN exams e ON ea.exam_id = e.id
    LEFT JOIN subjects s ON e.subject_id = s.id
    WHERE ea.student_id = ?
    ORDER BY ea.end_time DESC");
$past_exams->execute([$student_id]);
$my_attempts = $past_exams->fetchAll();

$total_marks_stmt = $pdo->prepare("SELECT SUM(marks) as total FROM exam_questions WHERE exam_id = ?");
?>
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $page_title; ?></title>
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
                <div class="d-flex justify-content-between align-items-center mb-5 animate-up">
                    <div>
                        <h2 class="fw-800 mb-1">Online Examinations</h2>
                        <p class="text-muted small fw-600">View and take your scheduled online exams</p>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- Available Exams -->
                    <div class="col-lg-7">
                        <h4 class="fw-800 mb-3">Available Exams</h4>
                        <?php if(empty($available_exams)): ?>
                            <div class="card-premium text-center py-5 animate-up">
                                <i class="fas fa-file-signature fa-4x text-muted mb-3"></i>
                                <h5 class="fw-800">No Exams Available</h5>
                                <p class="text-muted small">Check back later for upcoming exams</p>
                            </div>
                        <?php else: ?>
                            <?php foreach($available_exams as $exam): ?>
                            <div class="card-premium mb-3 animate-up">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <span class="badge bg-primary bg-opacity-10 text-primary fw-bold px-2 py-1 rounded-3 small">
                                            <?php echo $exam['subject_name'] ?? 'General'; ?>
                                        </span>
                                        <span class="badge bg-info bg-opacity-10 text-info fw-bold px-2 py-1 rounded-3 small ms-1">
                                            <?php echo $exam['class']; ?>
                                        </span>
                                    </div>
                                    <span class="badge bg-warning bg-opacity-10 text-warning fw-bold px-3 py-2 rounded-3">
                                        <i class="fas fa-clock me-1"></i> <?php echo $exam['duration_minutes']; ?> min
                                    </span>
                                </div>
                                <h5 class="fw-800 mb-1"><?php echo htmlspecialchars($exam['title']); ?></h5>
                                <p class="text-muted small mb-3"><?php echo htmlspecialchars($exam['description']); ?></p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i>
                                        <?php echo date('M d, H:i', strtotime($exam['start_time'])); ?> - <?php echo date('M d, H:i', strtotime($exam['end_time'])); ?>
                                    </small>
                                    <?php if($exam['attempted'] > 0): ?>
                                        <?php if($exam['attempt_status'] === 'completed'): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success fw-bold px-3 py-2 rounded-3">
                                                <i class="fas fa-check me-1"></i> Completed
                                            </span>
                                        <?php else: ?>
                                            <a href="take_exam.php?exam_id=<?php echo $exam['id']; ?>" class="btn btn-warning btn-sm rounded-3 fw-bold">
                                                <i class="fas fa-play me-1"></i> Resume
                                            </a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <a href="take_exam.php?exam_id=<?php echo $exam['id']; ?>" class="btn btn-premium btn-sm rounded-3 fw-800">
                                            <i class="fas fa-play me-1"></i> Start Exam
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Past Attempts -->
                    <div class="col-lg-5">
                        <h4 class="fw-800 mb-3">My Results</h4>
                        <?php if(empty($my_attempts)): ?>
                            <div class="card-premium text-center py-4 animate-up">
                                <i class="fas fa-history fa-3x text-muted mb-3"></i>
                                <p class="text-muted small mb-0">No exam history yet</p>
                            </div>
                        <?php else: ?>
                            <?php foreach($my_attempts as $attempt): 
                                $total_marks_stmt->execute([$attempt['id']]);
                                $total_marks = $total_marks_stmt->fetchColumn() ?: 0;
                                $percent = $total_marks > 0 ? ($attempt['score'] / $total_marks) * 100 : 0;
                            ?>
                            <div class="card-premium mb-3 animate-up">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <span class="badge bg-primary bg-opacity-10 text-primary fw-bold px-2 py-1 rounded-3 small">
                                            <?php echo $attempt['subject_name'] ?? 'General'; ?>
                                        </span>
                                    </div>
                                    <span class="badge <?php echo $percent >= 40 ? 'bg-success' : 'bg-danger'; ?> bg-opacity-10 text-<?php echo $percent >= 40 ? 'success' : 'danger'; ?> fw-bold px-3 py-2 rounded-3">
                                        <?php echo $percent >= 40 ? 'PASS' : 'FAIL'; ?>
                                    </span>
                                </div>
                                <h5 class="fw-800 mb-1"><?php echo htmlspecialchars($attempt['title']); ?></h5>
                                <div class="d-flex justify-content-between align-items-center pt-2">
                                    <div>
                                        <span class="fw-800 fs-4 text-primary"><?php echo $attempt['score']; ?></span>
                                        <span class="text-muted small">/ <?php echo $total_marks; ?></span>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold <?php echo $percent >= 60 ? 'text-success' : ($percent >= 40 ? 'text-warning' : 'text-danger'); ?>">
                                            <?php echo round($percent, 1); ?>%
                                        </div>
                                        <small class="text-muted"><?php echo date('M d, H:i', strtotime($attempt['end_time'])); ?></small>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
