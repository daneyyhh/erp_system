<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'parent') {
    header("Location: ../login.php");
    exit();
}
require_once('../config/db.php');

$page_title = "Ward Attendance | Scholarly";

// Mock Ward Selection (In a real app, this would be tied to parent_id)
$student_stmt = $pdo->query("SELECT id, roll_no, user_id FROM students LIMIT 1");
$ward = $student_stmt->fetch();
$ward_id = $ward['id'];
$ward_user_id = $ward['user_id'];

$stmt_name = $pdo->prepare("SELECT name FROM users WHERE id = ?");
$stmt_name->execute([$ward_user_id]);
$ward_name = $stmt_name->fetchColumn();

// Fetch Real Attendance Summary for Ward
$query = "SELECT s.name, 
          COUNT(a.id) as total_classes,
          SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count,
          SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late_count
          FROM subjects s
          LEFT JOIN attendance a ON s.id = a.subject_id AND a.student_id = ?
          GROUP BY s.id";
$stmt = $pdo->prepare($query);
$stmt->execute([$ward_id]);
$subjects = $stmt->fetchAll();

$total_present = 0; $total_sessions = 0;
foreach($subjects as $s) {
    if($s['total_classes'] > 0) {
        $total_sessions += $s['total_classes'];
        $total_present += ($s['present_count'] + ($s['late_count'] * 0.5));
    }
}
$overall_pct = ($total_sessions > 0) ? round(($total_present / $total_sessions) * 100) : 0;
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
                        <h2 class="fw-800 mb-1">Ward Attendance Monitor</h2>
                        <p class="text-muted small">Viewing records for <b><?php echo $ward_name; ?> (Roll: <?php echo $ward['roll_no']; ?>)</b></p>
                    </div>
                </div>

                <div class="card border-0 shadow-sm p-5 text-center mb-5" style="border-radius: 40px; background: #fff;">
                    <div class="row align-items-center">
                        <div class="col-md-4 border-end">
                            <h5 class="text-muted small fw-bold mb-2 uppercase">TOTAL SESSIONS</h5>
                            <h2 class="fw-900"><?php echo $total_sessions; ?></h2>
                        </div>
                        <div class="col-md-4 border-end">
                            <h5 class="text-muted small fw-bold mb-2 uppercase">OVERALL PERCENTAGE</h5>
                            <h1 class="fw-900 text-primary"><?php echo $overall_pct; ?>%</h1>
                            <div class="progress mx-auto mt-3" style="height: 6px; width: 100px; background: #f1f5f9;">
                                <div class="progress-bar bg-primary" style="width: <?php echo $overall_pct; ?>%"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <h5 class="text-muted small fw-bold mb-2 uppercase">DEFICIT SESSIONS</h5>
                            <h2 class="fw-900 text-danger"><?php echo $total_sessions - floor($total_present); ?></h2>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm p-5" style="border-radius: 30px;">
                    <h5 class="fw-800 mb-4">Subject Performance</h5>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr class="text-muted small">
                                    <th>SUBJECT</th><th>CLASSES HELD</th><th>ATTENDED (P/L)</th><th>PERCENTAGE</th><th>STATUS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($subjects as $sub): 
                                    $s_total = $sub['total_classes'];
                                    $s_present = $sub['present_count'] + ($sub['late_count'] * 0.5);
                                    $s_pct = ($s_total > 0) ? round(($s_present / $s_total) * 100) : 0;
                                    $badge_class = ($s_pct < 75) ? 'bg-danger text-danger' : 'bg-success text-success';
                                    $status_text = ($s_pct < 75) ? 'Critical' : 'Good';
                                ?>
                                <tr>
                                    <td class="fw-bold"><?php echo $sub['name']; ?></td>
                                    <td><?php echo $s_total; ?></td>
                                    <td><?php echo $sub['present_count']; ?>P / <?php echo $sub['late_count']; ?>L</td>
                                    <td class="fw-bold"><?php echo $s_pct; ?>%</td>
                                    <td><span class="badge <?php echo $badge_class; ?> bg-opacity-10 fw-bold px-3 py-1"><?php echo $status_text; ?></span></td>
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
