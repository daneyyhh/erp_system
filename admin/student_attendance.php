<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
require_once('../config/db.php');

$roll = $_GET['id'] ?? '';
$stmt = $pdo->prepare("SELECT s.id, u.name, s.class, s.roll_no FROM students s JOIN users u ON s.user_id = u.id WHERE s.roll_no = ?");
$stmt->execute([$roll]);
$student = $stmt->fetch();

if (!$student) { die("Student not found."); }

$query = "SELECT sub.name as subject_name, 
          COUNT(a.id) as total,
          SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present
          FROM subjects sub
          LEFT JOIN attendance a ON sub.id = a.subject_id AND a.student_id = ?
          GROUP BY sub.id";
$stmt = $pdo->prepare($query);
$stmt->execute([$student['id']]);
$report = $stmt->fetchAll();

$page_title = "Individual Report: " . $student['name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css?v=2">
</head>
<body>
    <div class="wrapper">
        <?php include('../includes/sidebar.php'); ?>
        <div class="main-content">
            <?php include('../includes/topbar.php'); ?>
            <div class="content-area">
                <div class="mb-5">
                    <a href="students_list.php" class="btn btn-sm btn-link text-blue mb-2 px-0"><i class="fas fa-arrow-left me-2"></i>Back to Directory</a>
                    <h2 class="text-white fw-bold"><?php echo $student['name']; ?></h2>
                    <p class="text-muted"><?php echo $student['class']; ?> | ID: <?php echo $student['roll_no']; ?></p>
                </div>

                <div class="card glass">
                    <h5 class="mb-4">Subject-wise Analytics</h5>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>Total Lectures</th>
                                    <th>Attended</th>
                                    <th>Percentage</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($report as $r): 
                                    $pct = ($r['total'] > 0) ? round(($r['present']/$r['total'])*100) : 0;
                                ?>
                                <tr>
                                    <td class="fw-bold"><?php echo $r['subject_name']; ?></td>
                                    <td><?php echo $r['total']; ?></td>
                                    <td><?php echo $r['present']; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="progress flex-grow-1" style="height: 6px;">
                                                <div class="progress-bar <?php echo $pct < 75 ? 'bg-danger' : 'bg-success'; ?>" style="width: <?php echo $pct; ?>%"></div>
                                            </div>
                                            <span class="fw-bold"><?php echo $pct; ?>%</span>
                                        </div>
                                    </td>
                                    <td>
                                        <i class="fas fa-circle ms-2" style="font-size: 8px; color: <?php echo $pct < 75 ? '#ef4444' : '#10b981'; ?>"></i>
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
