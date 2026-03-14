<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}
require_once('../config/db.php');

$page_title = "My Attendance | Scholarly";

// Fetch Student Data
$stmt = $pdo->prepare("SELECT id, roll_no FROM students WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch();
$student_id = $student['id'];

// Fetch Real Attendance Summary by Subject
$query = "SELECT s.name, s.code, 
          COUNT(a.id) as total_classes,
          SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count,
          SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late_count
          FROM subjects s
          LEFT JOIN attendance a ON s.id = a.subject_id AND a.student_id = ?
          GROUP BY s.id";
$stmt = $pdo->prepare($query);
$stmt->execute([$student_id]);
$subjects = $stmt->fetchAll();

// Fetch this month's attendance for the calendar
$month = date('m');
$year = date('Y');
$days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$attendance_days = [];
$stmt = $pdo->prepare("SELECT date, status FROM attendance WHERE student_id = ? AND MONTH(date) = ? AND YEAR(date) = ?");
$stmt->execute([$student_id, $month, $year]);
while($row = $stmt->fetch()) {
    $attendance_days[date('j', strtotime($row['date']))] = $row['status'];
}
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
<body style="background: #f8fafc;">
    <div class="wrapper">
        <?php include('../includes/sidebar.php'); ?>
        <div class="main-content">
            <?php include('../includes/topbar.php'); ?>
            <div class="content-area">
                <div class="d-flex justify-content-between align-items-center mb-5">
                    <div>
                        <h2 class="fw-800 mb-1">My Performance Tracker</h2>
                        <p class="text-muted small">Real-time attendance analytics for current semester</p>
                    </div>
                </div>

                <div class="row g-4">
                    <?php foreach($subjects as $sub): 
                        $total = $sub['total_classes'];
                        $present = $sub['present_count'] + ($sub['late_count'] * 0.5); // Late counts as half
                        $pct = ($total > 0) ? round(($present / $total) * 100, 1) : 0;
                        $color = ($pct < 75) ? 'danger' : 'success';
                    ?>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm p-4" style="border-radius: 30px;">
                            <div class="d-flex justify-content-between align-items-start mb-4">
                                <div>
                                    <h6 class="fw-800 mb-0"><?php echo $sub['name']; ?></h6>
                                    <code class="smallest text-primary"><?php echo $sub['code']; ?></code>
                                </div>
                                <div class="text-end">
                                    <h4 class="fw-900 mb-0 text-<?php echo $color; ?>"><?php echo $pct; ?>%</h4>
                                </div>
                            </div>
                            <div class="progress mb-3" style="height: 6px; background: #f1f5f9;">
                                <div class="progress-bar bg-<?php echo $color; ?>" style="width: <?php echo $pct; ?>%"></div>
                            </div>
                            <div class="d-flex justify-content-between smallest fw-bold text-muted">
                                <span><?php echo $sub['present_count']; ?>P / <?php echo $sub['late_count']; ?>L / <?php echo $total; ?> Total</span>
                                <span>Status: <?php echo ($pct < 75) ? 'Shortage' : 'Excellent'; ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="card border-0 shadow-sm p-5 mt-5" style="border-radius: 30px;">
                    <h5 class="fw-800 mb-4"><?php echo date('F Y'); ?> Heatmap</h5>
                    <div class="row text-center mb-4">
                        <div class="col"><b class="small d-block text-muted">Su</b></div>
                        <div class="col"><b class="small d-block text-muted">Mo</b></div>
                        <div class="col"><b class="small d-block text-muted">Tu</b></div>
                        <div class="col"><b class="small d-block text-muted">We</b></div>
                        <div class="col"><b class="small d-block text-muted">Th</b></div>
                        <div class="col"><b class="small d-block text-muted">Fr</b></div>
                        <div class="col"><b class="small d-block text-muted">Sa</b></div>
                    </div>
                    <div class="row g-2">
                        <?php 
                        $first_day = date('w', strtotime("$year-$month-01"));
                        for($i=0; $i<$first_day; $i++) echo '<div class="col" style="flex: 0 0 14.28%;"></div>';
                        
                        for($i=1; $i<=$days_in_month; $i++): 
                            $status = $attendance_days[$i] ?? 'none';
                            $color = '#f8fafc'; $text = '#cbd5e1';
                            if($status == 'present') { $color = '#dcfce7'; $text = '#15803d'; }
                            if($status == 'absent') { $color = '#fee2e2'; $text = '#b91c1c'; }
                            if($status == 'late') { $color = '#fef3c7'; $text = '#b45309'; }
                        ?>
                            <div class="col" style="flex: 0 0 14.28%;">
                                <div class="p-3 rounded-4 fw-bold small text-center" style="background: <?php echo $color; ?>; color: <?php echo $text; ?>;">
                                    <?php echo $i; ?>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
