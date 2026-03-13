<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'parent') {
    header("Location: ../login.php");
    exit();
}
require_once('../config/db.php');

// Mock ward selection
$student_stmt = $pdo->query("SELECT s.id, u.name FROM students s JOIN users u ON s.user_id = u.id LIMIT 1");
$ward = $student_stmt->fetch();
$ward_id = $ward['id'] ?? 0;

$query = "SELECT s.name as subject_name, m.total, m.grade 
          FROM subjects s 
          LEFT JOIN marks m ON s.id = m.subject_id AND m.student_id = ?
          ORDER BY s.semester ASC";
$stmt = $pdo->prepare($query);
$stmt->execute([$ward_id]);
$results = $stmt->fetchAll();

$page_title = "Ward Progress Report | Scholarly";
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
            <div class="content area">
                <div class="d-flex justify-content-between align-items-center mb-5">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary bg-opacity-10 p-3 rounded-circle text-primary">
                            <i class="fas fa-graduation-cap fa-lg"></i>
                        </div>
                        <div>
                            <h2 class="fw-800 mb-0"><?php echo $ward['name']; ?></h2>
                            <p class="text-muted small mb-0">Academic Performance Tracking</p>
                        </div>
                    </div>
                    <button class="btn btn-primary px-4 py-2 shadow-sm fw-bold" style="border-radius:12px;" onclick="window.print()">
                        <i class="fas fa-download me-2"></i> Export Report
                    </button>
                </div>

                <div class="card border-0 shadow-sm p-5" style="border-radius: 30px;">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr class="text-muted small">
                                    <th>SUBJECT</th><th>RAW SCORE</th><th>LETTER GRADE</th><th class="text-end">ASSESSMENT</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($results as $r): 
                                    $score = $r['total'] ?? 0;
                                    $status = ($score >= 40) ? 'Satisfactory' : 'Needs Improvement';
                                    $badge = ($score >= 40) ? 'bg-success' : 'bg-danger';
                                ?>
                                <tr>
                                    <td class="fw-bold"><?php echo $r['subject_name']; ?></td>
                                    <td><b class="fs-5"><?php echo $score; ?></b> <span class="text-muted smallest">/ 100</span></td>
                                    <td><span class="badge bg-primary bg-opacity-10 text-primary fw-bold px-3 py-1"><?php echo $r['grade'] ?? 'N/A'; ?></span></td>
                                    <td class="text-end">
                                        <span class="badge <?php echo $badge; ?> bg-opacity-10 <?php echo str_replace('bg-', 'text-', $badge); ?> fw-bold px-3 py-1"><?php echo $status; ?></span>
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
