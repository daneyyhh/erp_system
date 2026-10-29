<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'teacher') {
    header("Location: ../login.php");
    exit();
}
require_once('../config/db.php');

$page_title = "Exam Results | Project ERP";

$exam_id = $_GET['exam_id'] ?? null;

if (!$exam_id) {
    header("Location: exams.php");
    exit();
}

$exam = $pdo->prepare("SELECT e.*, s.name as subject_name 
                        FROM exams e 
                        LEFT JOIN subjects s ON e.subject_id = s.id 
                        WHERE e.id = ? AND e.created_by = ?");
$exam->execute([$exam_id, $_SESSION['user_id']]);
$exam = $exam->fetch();

if (!$exam) {
    header("Location: exams.php");
    exit();
}

$results = $pdo->prepare("SELECT ea.id as attempt_id, ea.start_time, ea.end_time, ea.score, ea.status,
                          u.name as student_name, s.roll_no
                          FROM exam_attempts ea
                          JOIN students s ON ea.student_id = s.id
                          JOIN users u ON s.user_id = u.id
                          WHERE ea.exam_id = ?
                          ORDER BY ea.score DESC");
$results->execute([$exam_id]);
$attempts = $results->fetchAll();

$total_marks = $pdo->prepare("SELECT SUM(marks) FROM exam_questions WHERE exam_id = ?");
$total_marks->execute([$exam_id]);
$total_possible = $total_marks->fetchColumn();

$stats = [
    'total' => count($attempts),
    'completed' => count(array_filter($attempts, fn($a) => $a['status'] === 'completed')),
    'avg_score' => count($attempts) > 0 ? array_sum(array_map(fn($a) => $a['score'], $attempts)) / count($attempts) : 0,
    'highest' => count($attempts) > 0 ? max(array_map(fn($a) => $a['score'], $attempts)) : 0,
    'lowest' => count($attempts) > 0 ? min(array_map(fn($a) => $a['score'], $attempts)) : 0
];

if (isset($_GET['export'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="exam_results_' . $exam_id . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Roll No', 'Student Name', 'Score', 'Total Marks', 'Percentage', 'Status', 'Start Time', 'End Time']);
    foreach ($attempts as $a) {
        fputcsv($output, [
            $a['roll_no'],
            $a['student_name'],
            $a['score'],
            $total_possible,
            $total_possible > 0 ? round(($a['score'] / $total_possible) * 100, 1) . '%' : '0%',
            $a['status'],
            $a['start_time'],
            $a['end_time'] ?? 'N/A'
        ]);
    }
    fclose($output);
    exit();
}
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
                <div class="d-flex justify-content-between align-items-center mb-4 animate-up">
                    <div>
                        <a href="exams.php" class="btn btn-sm btn-outline-secondary rounded-3 mb-2">
                            <i class="fas fa-arrow-left me-1"></i> Back to Exams
                        </a>
                        <h2 class="fw-800 mb-1"><?php echo htmlspecialchars($exam['title']); ?></h2>
                        <p class="text-muted small fw-600">
                            <span class="badge bg-primary bg-opacity-10 text-primary fw-bold px-2 py-1 rounded-3">
                                <?php echo $exam['subject_name'] ?? 'General'; ?>
                            </span>
                            <span class="ms-2"><?php echo $exam['class']; ?></span>
                            <span class="ms-2">| Total Marks: <?php echo $total_possible; ?></span>
                        </p>
                    </div>
                    <a href="?exam_id=<?php echo $exam_id; ?>&export=1" class="btn btn-success rounded-4 fw-800">
                        <i class="fas fa-download me-2"></i> Export CSV
                    </a>
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <div class="card-premium text-center p-4 animate-up">
                            <div class="text-muted small fw-bold mb-1">TOTAL ATTEMPTS</div>
                            <h2 class="fw-900 text-primary mb-0"><?php echo $stats['total']; ?></h2>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card-premium text-center p-4 animate-up">
                            <div class="text-muted small fw-bold mb-1">COMPLETED</div>
                            <h2 class="fw-900 text-success mb-0"><?php echo $stats['completed']; ?></h2>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card-premium text-center p-4 animate-up">
                            <div class="text-muted small fw-bold mb-1">AVERAGE SCORE</div>
                            <h2 class="fw-900 text-warning mb-0"><?php echo round($stats['avg_score'], 1); ?></h2>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card-premium text-center p-4 animate-up">
                            <div class="text-muted small fw-bold mb-1">HIGHEST</div>
                            <h2 class="fw-900 text-info mb-0"><?php echo $stats['highest']; ?></h2>
                        </div>
                    </div>
                </div>

                <div class="card-premium animate-up">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr class="smallest text-muted uppercase fw-800">
                                    <th>Roll No</th>
                                    <th>Student Name</th>
                                    <th class="text-center">Score</th>
                                    <th class="text-center">Percentage</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Started</th>
                                    <th class="text-center">Completed</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($attempts)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="fas fa-inbox fa-3x mb-3"></i>
                                        <p>No students have attempted this exam yet</p>
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach($attempts as $a): ?>
                                    <tr class="border-bottom" style="border-color: var(--border) !important;">
                                        <td class="py-3"><code class="fw-bold"><?php echo $a['roll_no']; ?></code></td>
                                        <td class="py-3 fw-bold"><?php echo htmlspecialchars($a['student_name']); ?></td>
                                        <td class="py-3 text-center">
                                            <span class="fw-800 fs-5 <?php echo ($a['score'] / $total_possible * 100) >= 40 ? 'text-success' : 'text-danger'; ?>">
                                                <?php echo $a['score']; ?>/<?php echo $total_possible; ?>
                                            </span>
                                        </td>
                                        <td class="py-3 text-center">
                                            <?php 
                                                $percent = $total_possible > 0 ? ($a['score'] / $total_possible) * 100 : 0;
                                                $badge_class = $percent >= 60 ? 'success' : ($percent >= 40 ? 'warning' : 'danger');
                                            ?>
                                            <span class="badge bg-<?php echo $badge_class; ?> bg-opacity-10 text-<?php echo $badge_class; ?> fw-bold px-3 py-2 rounded-3">
                                                <?php echo round($percent, 1); ?>%
                                            </span>
                                        </td>
                                        <td class="py-3 text-center">
                                            <span class="badge <?php echo $a['status'] === 'completed' ? 'bg-success' : 'bg-warning'; ?> bg-opacity-10 text-<?php echo $a['status'] === 'completed' ? 'success' : 'warning'; ?> fw-bold px-3 py-2 rounded-3">
                                                <?php echo ucfirst($a['status']); ?>
                                            </span>
                                        </td>
                                        <td class="py-3 text-center text-muted small">
                                            <?php echo date('M d, H:i', strtotime($a['start_time'])); ?>
                                        </td>
                                        <td class="py-3 text-center text-muted small">
                                            <?php echo $a['end_time'] ? date('M d, H:i', strtotime($a['end_time'])) : '<span class="text-warning">In Progress</span>'; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
