<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}
require_once('../config/db.php');

$stmt = $pdo->prepare("SELECT s.id FROM students s WHERE s.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$student_id = $stmt->fetchColumn();

$query = "SELECT s.name as subject_name, m.internal, m.external, m.total, m.grade 
          FROM subjects s 
          LEFT JOIN marks m ON s.id = m.subject_id AND m.student_id = ?
          WHERE s.semester = (SELECT semester FROM students WHERE id = ?)";
$stmt = $pdo->prepare($query);
$stmt->execute([$student_id, $student_id]);
$results = $stmt->fetchAll();

$page_title = "Academic Results | Scholarly";
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
                        <h2 class="fw-800 mb-1">Academic Transcript</h2>
                        <p class="text-muted small">Official record of internal and external examination scores</p>
                    </div>
                    <button class="btn btn-primary px-4 py-2 shadow-sm fw-bold" style="border-radius:12px;" onclick="window.print()">
                        <i class="fas fa-print me-2"></i> Print Transcript
                    </button>
                </div>
                
                <div class="row g-4 mb-5">
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm p-4 text-center" style="border-radius:24px; background: #eef2ff;">
                            <h6 class="fw-bold text-primary mb-1 small uppercase">CURRENT CGPA</h6>
                            <h2 class="fw-900 text-primary mb-0">8.82</h2>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm p-5" style="border-radius: 30px;">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr class="text-muted small">
                                    <th>SUBJECT</th><th>INTERNAL (40)</th><th>EXTERNAL (60)</th><th>TOTAL (100)</th><th class="text-end">GRADE</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($results as $r): ?>
                                <tr>
                                    <td class="fw-bold"><?php echo $r['subject_name']; ?></td>
                                    <td><?php echo $r['internal'] ?? '-'; ?></td>
                                    <td><?php echo $r['external'] ?? '-'; ?></td>
                                    <td><b class="text-primary"><?php echo $r['total'] ?? '-'; ?></b></td>
                                    <td class="text-end">
                                        <?php if($r['grade']): ?>
                                            <span class="badge bg-primary bg-opacity-10 text-primary fw-bold px-3 py-1"><?php echo $r['grade']; ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-warning bg-opacity-10 text-warning fw-bold px-3 py-1">Awaited</span>
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
